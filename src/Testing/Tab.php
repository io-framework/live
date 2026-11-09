<?php

namespace Io\Live\Testing;

use Closure;
use LogicException;
use PHPUnit\Framework\Assert;
use Throwable;
use Io\App;
use Io\Http\Request;
use Io\Live\{Circuit, LivePlugin};

/**
 * One browser tab in a test, without a browser: the app's components with its services, driven through the same
 * messages the browser sends, so a test proves the template's wiring too.
 *
 *     $tab = Tab::for($app);
 *     $button = $tab->mount('app-add-to-cart', ['product' => 3]);
 *     $button->click('add')->assertSee('1 in your cart');
 *     $tab->advance(1.0);                              // #[Every] timers
 *
 * An error the browser would only see as `data-live-error` fails the test with its own stack. Two tabs of one app
 * share its services, so a Broadcast reaches the other.
 */
final class Tab
{
    private Circuit $circuit;

    /** @var array<string, string> The HTML the browser has, by top-level component */
    private array $html = [];

    /** @var list<Throwable> */
    private array $errors = [];

    /** @var list<array{float, Closure(): void, float, bool}> seconds, work, when it runs next, cancelled */
    private array $timers = [];

    private float $now = 0.0;

    private int $mounted = 0;

    private ?string $navigation = null;

    /** @var array<array-key, mixed> The last answer to live.invoke() */
    private array $returned = [];

    /** @var array<string, mixed> the query values the address shows, by key */
    private array $query = [];

    /** @var array<string, mixed> What the browser's functions return, by path */
    private array $answers = [];

    /** @var list<array{string, list<mixed>}> The functions the components called, in order */
    private array $invoked = [];

    private function __construct(private readonly LivePlugin $live, ?Request $request)
    {
        $this->circuit = new Circuit(
            $live,
            function (string $message): void {
                $this->receive($message);
            },
            function (Throwable $e): void {
                $this->errors[] = $e;
            },
            function (float $seconds, Closure $work): Closure {
                $i = count($this->timers);
                $this->timers[] = [$seconds, $work, $this->now + $seconds, false];
                return function () use ($i): void {
                    $this->timers[$i][3] = true;
                };
            },
            $request,
        );
    }

    /** A tab of $app, once attach() has booted its plugins; $request is the connection's (cookies, the user). */
    public static function for(App $app, ?Request $request = null): self
    {
        return new self($app->find(LivePlugin::class) ?? throw new LogicException('Tab::for() needs an app with LivePlugin, after attach()'), $request);
    }

    /**
     * The HTML a page shows before any connection: for what users without JavaScript see.
     *
     * @param array<string, mixed> $inputs
     */
    public static function page(App $app, string $selector, array $inputs = [], ?Request $request = null): string
    {
        return Circuit::page($app->find(LivePlugin::class) ?? throw new LogicException('Tab::page() needs an app with LivePlugin, after attach()'), $selector, $inputs, '', $request);
    }

    /** Puts a service, such as a fake, into this tab before its components are created, for the #[Scoped] class it is or extends. */
    public function with(object $service): self
    {
        $this->circuit->services()->put($service);
        return $this;
    }

    /**
     * Places a top-level component as the browser does when it connects, and renders it.
     *
     * @param array<string, mixed> $inputs As the page passes them
     */
    public function mount(string $selector, array $inputs = []): Mounted
    {
        $id = 'c' . $this->mounted++;
        $this->send(['t' => 'mount', 'id' => $id, 'c' => $selector, 'p' => (object) $inputs]);
        return new Mounted($this, $id);
    }

    /**
     * What a function returns when a component calls it through Js::invoke(), or the Closure that answers its
     * arguments; a Closure that throws is a function that fails. Any other function returns null.
     */
    public function answer(string $function, mixed $value): self
    {
        $this->answers[$function] = $value;
        return $this;
    }

    /** That a component called $function through Js, with these arguments. */
    public function assertInvoked(string $function, mixed ...$args): self
    {
        Assert::assertContains([$function, array_values($args)], $this->invoked, 'No such call of JavaScript in this tab');
        return $this;
    }

    /** Lets $seconds pass: the #[Every] timers that are due run, in order. */
    public function advance(float $seconds): self
    {
        $until = $this->now + $seconds;
        while (true) {
            $due = null;
            foreach ($this->timers as $i => [, , $at, $cancelled]) {
                if (!$cancelled && $at <= $until && ($due === null || $at < $this->timers[$due][2])) {
                    $due = $i;
                }
            }
            if ($due === null) {
                break;
            }
            [$every, $work, $at] = $this->timers[$due];
            $this->now = $at;
            $this->timers[$due][2] = $at + $every;
            $work();
            $this->rethrow();
        }
        $this->now = $until;
        return $this;
    }

    /**
     * That the browser's address shows these #[Input(query: ...)] values, by query key; null for one it leaves out
     *
     * @param array<string, mixed> $query
     */
    public function assertQuery(array $query): self
    {
        Assert::assertSame($query, array_intersect_key($this->query, $query), 'The address shows other query values');
        return $this;
    }

    /** That the last navigate() of an action or timer in this tab asked for $url. */
    public function assertNavigatedTo(string $url): self
    {
        Assert::assertSame($url, $this->navigation, 'The tab was sent elsewhere');
        return $this;
    }

    /**
     * @internal What the browser would send
     *
     * @param array<string, mixed> $message
     */
    public function send(array $message): void
    {
        $this->circuit->handle(json_encode($message, JSON_THROW_ON_ERROR));
        $this->rethrow();
    }

    /** @internal The HTML the browser has of a top-level component */
    public function html(string $id): string
    {
        return $this->html[$id] ?? throw new LogicException("No component '$id' in this tab");
    }

    /** @internal */
    public function circuit(): Circuit
    {
        return $this->circuit;
    }

    private function receive(string $message): void
    {
        $message = json_decode($message, true);
        if (!is_array($message)) {
            return;
        }
        if (($message['t'] ?? null) === 'js' && is_string($message['f'] ?? null) && is_array($message['a'] ?? null)) {
            $this->javascript($message['f'], array_values($message['a']), $message['r'] ?? null);
        } elseif (($message['t'] ?? null) === 'return') {
            $this->returned = $message;
        } elseif (($message['t'] ?? null) === 'navigate' && is_string($message['url'] ?? null)) {
            $this->navigation = $message['url'];
        } elseif (($message['t'] ?? null) === 'query' && is_array($message['q'] ?? null)) {
            foreach ($message['q'] as $key => $value) {
                $this->query[(string) $key] = $value;
            }
        } elseif (is_string($message['id'] ?? null) && is_string($message['html'] ?? null)) {
            $this->html[$message['id']] = $message['html'];
        }
    }

    /**
     * The function runs, and a call that waits gets its answer as the browser's would come.
     *
     * @param list<mixed> $args
     */
    private function javascript(string $function, array $args, mixed $id): void
    {
        $this->invoked[] = [$function, $args];
        $answer = ['t' => 'answer', 'r' => $id, 'v' => null];
        $value = $this->answers[$function] ?? null;
        try {
            $answer['v'] = $value instanceof Closure ? $value(...$args) : $value;
        } catch (Throwable $e) {
            unset($answer['v']);
            $answer['e'] = $e->getMessage();
        }
        if ($id !== null) {
            $this->circuit->handle(json_encode($answer, JSON_THROW_ON_ERROR));
        }
    }

    /** @internal What a #[JsInvokable] method returned to the last live.invoke() */
    public function returned(): mixed
    {
        if (($this->returned['error'] ?? false) === true) {
            throw new LogicException('The #[JsInvokable] method failed');
        }
        return $this->returned['v'] ?? null;
    }

    private function rethrow(): void
    {
        $error = array_shift($this->errors);
        if ($error !== null) {
            $this->errors = [];
            throw $error;
        }
    }
}
