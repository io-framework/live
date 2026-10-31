<?php

namespace Io\Live;

use Closure;
use Io\SignOuts;

/**
 * @internal This worker's open WebSockets by the sign-in they opened with: when Io's SignOuts announces that one
 * ended early (sign-out, new password, disabled user), its connections close. The browser connects again through
 * the app's middlewares, and the circuit starts over for whoever it is then.
 */
final class Connections
{
    /** @var array<string, array<int, Closure(): void>> Closes the connection, by sign-in and connection */
    private static array $open = [];

    private static bool $listening = false;

    /**
     * Keeps $close until forget(): the connection $key, signed in with the claims $principal. A principal without
     * the claim SignOuts::CLAIM (a JWT, nobody) can't end early and is not kept.
     *
     * @param array<string, mixed>|null $principal
     * @param Closure(): void $close
     */
    public static function opened(?array $principal, int $key, Closure $close): void
    {
        $signIn = $principal[SignOuts::CLAIM] ?? null;
        if (!is_string($signIn)) {
            return;
        }
        // Only once a connection needs it: a subscription keeps a script without a server running
        if (!self::$listening) {
            self::$listening = true;
            SignOuts::listen(self::ended(...));
        }
        self::$open[$signIn][$key] = $close;
    }

    /** @param array<string, mixed>|null $principal */
    public static function closed(?array $principal, int $key): void
    {
        $signIn = $principal[SignOuts::CLAIM] ?? null;
        if (!is_string($signIn)) {
            return;
        }
        unset(self::$open[$signIn][$key]);
        if ((self::$open[$signIn] ?? []) === []) {
            unset(self::$open[$signIn]);
        }
    }

    /** @param list<string> $signIns */
    private static function ended(array $signIns): void
    {
        foreach ($signIns as $signIn) {
            foreach (self::$open[$signIn] ?? [] as $close) {
                $close();
            }
        }
    }

    /** @internal For tests */
    public static function reset(): void
    {
        self::$open = [];
        self::$listening = false;
    }
}
