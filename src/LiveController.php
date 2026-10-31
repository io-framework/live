<?php

namespace Io\Live;

use Throwable;
use Io\Attributes\{Controller, Get, Ignore, Inject};
use Io\Exceptions\ForbiddenException;
use Io\Http\{Request, Response};
use Io\Logger;

/**
 * @internal The browser's WebSocket: one Circuit per connection, left out of the OpenAPI document. A normal route, so the app's middlewares and guards
 * run before the handover; the handler then runs with the request's context (request id in the log).
 */
#[Controller]
#[Ignore]
final class LiveController
{
    // ponytail: fixed per worker; options once an app needs others
    private const PARK_SECONDS = 180.0;
    private const MAX_PARKED = 100;
    /** One client keeps at most this many parked, so it cannot push out everyone else's */
    private const MAX_PARKED_PER_CLIENT = 10;

    /**
     * @var array<string, array{Circuit, \Threading\Timer, string}> Circuits whose browser may come back, oldest
     * first, with their client
     */
    private static array $parked = [];

    /** This process, in dev: the browser sees a new one after a restart (--watch) and loads the page again. */
    private static ?string $boot = null;

    #[Inject]
    private LivePlugin $live;

    #[Inject]
    private Logger $logger;

    #[Get('/_live')]
    public function connect(Request $req, Response $res): void
    {
        // A WebSocket carries no CSRF header and the browser sends cookies along from any site: only our own pages
        if (!self::sameOrigin($req)) {
            throw new ForbiddenException('Live components only connect from this site');
        }
        $live = $this->live;
        $logger = $this->logger;
        $res->upgrade(\Http\Response::websocket(static function (\Http\WebSocket $socket) use ($live, $logger, $req): void {
            $send = static function (string $message) use ($socket): void {
                try {
                    $socket->send($message);
                } catch (\Http\ConnectionClosed) {
                    // Rendered after the browser left; the receive loop below ends the connection
                }
            };
            $circuit = null;
            $token = '';
            $close = static fn () => $socket->close();
            // A sign-out elsewhere closes it at once, not only once its principal's exp has passed
            Connections::opened($req->principal, spl_object_id($socket), $close);
            try {
                while (($message = $socket->receive()) !== null) {
                    if ($circuit !== null) {
                        $circuit->handle($message);
                        continue;
                    }
                    // The first message says whether the browser had a circuit before: {"t":"hello","token":"..."|null}
                    $hello = json_decode($message, true);
                    $asked = is_array($hello) && is_string($hello['token'] ?? null) ? $hello['token'] : '';
                    // And where the browser is: the request here is the WebSocket's, /_live
                    $page = is_array($hello) && is_string($hello['page'] ?? null) ? $hello['page'] : '/';
                    $circuit = self::unpark($asked);
                    // Someone else, or nobody any more after a logout: the components start over for this user
                    if ($circuit !== null && !$circuit->sameUser($req)) {
                        $circuit->close();
                        $circuit = null;
                    }
                    $resumed = $circuit !== null;
                    $token = $resumed ? $asked : bin2hex(random_bytes(16));
                    $circuit ??= new Circuit($live, $send, static fn (Throwable $e) => $logger->error('live: ' . $e->getMessage(), ['exception' => $e]), null, $req, $close, $page);
                    $send(json_encode(['t' => 'circuit', 'token' => $token, 'resumed' => $resumed] + ($live->isDev() ? ['boot' => self::$boot ??= bin2hex(random_bytes(8)), 'reload' => $live->reloaded(), 'editor' => $live->editor()] : []), JSON_THROW_ON_ERROR));
                    if ($resumed) {
                        $circuit->reattach($send, $req, $close, $page);
                    }
                }
            } finally {
                Connections::closed($req->principal, spl_object_id($socket));
                // A circuit without components has nothing to come back to, and must not push real ones out
                if ($circuit !== null && !$circuit->isEmpty()) {
                    self::park($token, $circuit, self::client($req));
                } else {
                    $circuit?->close();
                }
            }
        }));
    }

    /**
     * Keeps a circuit whose connection dropped, so the browser can come back to it, for a
     * while, then it is closed. Per worker: a browser that reconnects to another worker starts over.
     */
    private static function park(string $token, Circuit $circuit, string $client): void
    {
        $circuit->detach();
        $own = array_keys(array_filter(self::$parked, static fn (array $entry) => $entry[2] === $client));
        if (count($own) >= self::MAX_PARKED_PER_CLIENT) {
            self::unpark((string) $own[0])?->close();
        } elseif (count(self::$parked) >= self::MAX_PARKED) {
            self::unpark(self::oldestOfBusiest())?->close();
        }
        // Detached from this request: the connection is over, and Tenma ends the request's own timers with it
        $expiry = null;
        \Threading\Task::detach(static function () use ($token, &$expiry): void {
            $expiry = \Threading\Timer::after(self::PARK_SECONDS, static fn () => self::unpark($token)?->close());
        });
        if ($expiry !== null) {
            self::$parked[$token] = [$circuit, $expiry, $client];
        }
    }

    /** When every place is taken: the oldest circuit of the client that holds the most */
    private static function oldestOfBusiest(): string
    {
        $counts = array_count_values(array_column(self::$parked, 2));
        arsort($counts);
        $busiest = (string) array_key_first($counts);
        foreach (self::$parked as $token => $entry) {
            if ($entry[2] === $busiest) {
                return (string) $token;
            }
        }
        return (string) array_key_first(self::$parked);
    }

    /** Whose circuit it is: the signed-in user, or else the address it came from */
    private static function client(Request $req): string
    {
        $subject = $req->principal['sub'] ?? null;
        if ($subject !== null) {
            return 'sub:' . json_encode($subject, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        return 'ip:' . $req->ip;
    }

    private static function unpark(string $token): ?Circuit
    {
        [$circuit, $expiry] = self::$parked[$token] ?? [null, null];
        unset(self::$parked[$token]);
        $expiry?->cancel();
        return $circuit;
    }

    private static function sameOrigin(Request $req): bool
    {
        $origin = parse_url($req->headers['Origin'] ?? '');
        $host = $req->headers['Host'] ?? '';
        if (!is_array($origin) || !isset($origin['host']) || $host === '') {
            return false;
        }
        $originHost = $origin['host'] . (isset($origin['port']) ? ':' . $origin['port'] : '');
        return strcasecmp($originHost, $host) === 0;
    }
}
