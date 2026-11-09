<?php

namespace Io\Live;

use Closure;
use Threading\{Cancelled, Task};

/**
 * The browser's JavaScript, for a component or a #[Scoped] service that injects it: calls a function on `window`
 * by its path ("localStorage.getItem", "app.chart.draw"). The arguments and the result travel as JSON.
 *
 * What the browser answers is input like a form field: it can answer anything. The path runs as it is, so it never
 * comes from a user.
 */
#[Scoped]
final class Js
{
    // ponytail: fixed for every call; a per-call limit once a function needs longer
    private const TIMEOUT_SECONDS = 60.0;

    /** Whether a browser listens. False during the page's first render and while the connection is away. */
    public private(set) bool $connected = true;

    private int $last = 0;

    /** @var array<int, array{mixed, ?string}|null> Calls without their end yet: null, else the answer and the error */
    private array $asked = [];

    /** @var array<int, Task> What the calls still waiting wait on */
    private array $waiters = [];

    /** @var list<array{string, list<mixed>}> */
    private array $later = [];

    /**
     * @internal The circuit creates it
     *
     * @param Closure(string): void $send
     */
    public function __construct(private Closure $send)
    {
    }

    /**
     * Calls the function and returns what it returned, a promise's value included; undefined comes back as null.
     * Only this fiber waits, the tab's next events wait with it. The page still shows the last render: to reach
     * what this action renders, use invokeVoid().
     *
     * @throws JsException The function threw, no browser is connected, or no answer came within a minute
     */
    public function invoke(string $function, mixed ...$args): mixed
    {
        if (!$this->connected) {
            throw new JsException("$function(): no browser is connected");
        }
        $this->flush();
        $id = ++$this->last;
        $this->asked[$id] = null;
        try {
            ($this->send)(self::call($function, array_values($args), $id));
            // In a test, or while a slow client held up the write, the answer is here already
            if ($this->asked[$id] === null) {
                $this->wait($id);
            }
            [$value, $error] = $this->asked[$id] ?? throw new JsException("$function(): no answer from the browser in time");
        } finally {
            unset($this->asked[$id]);
        }
        if ($error !== null) {
            throw new JsException("$function(): $error");
        }
        return $value;
    }

    /**
     * Calls the function once the action is done and its render is on the page, without waiting for it. A failure
     * shows in the browser's console. Without a browser it is dropped: a component that asks during the page's first
     * render mounts again when the browser connects, and asks again there.
     */
    public function invokeVoid(string $function, mixed ...$args): void
    {
        if ($this->connected) {
            $this->later[] = [$function, array_values($args)];
        }
    }

    /** @internal The action is done: what invokeVoid() kept goes out, behind its render. */
    public function flush(): void
    {
        foreach (array_splice($this->later, 0) as [$function, $args]) {
            ($this->send)(self::call($function, $args));
        }
    }

    /**
     * @internal The browser's answer to a call. It comes from the browser, so an ID nobody waits for is ignored.
     */
    public function answered(mixed $id, mixed $value, mixed $error): void
    {
        if (!is_int($id) || !array_key_exists($id, $this->asked)) {
            return;
        }
        $this->end($id, [$value, $error === null ? null : (is_string($error) ? $error : 'failed')]);
    }

    /** @internal The browser is back, or gone: what still waits fails, and invokeVoid() keeps nothing. */
    public function connected(bool $connected): void
    {
        $this->connected = $connected;
        $this->later = [];
        foreach (array_keys($this->asked) as $id) {
            $this->end($id, [null, 'the connection to the browser was lost']);
        }
    }

    /** @param array{mixed, ?string} $answer */
    private function end(int $id, array $answer): void
    {
        $this->asked[$id] = $answer;
        ($this->waiters[$id] ?? null)?->cancel();
    }

    /** Until the waiter is cancelled by the answer, or finds that none came in time. */
    private function wait(int $id): void
    {
        $this->waiters[$id] = Task::run(static function (): void {
            try {
                Task::delay(self::TIMEOUT_SECONDS);
            } catch (Cancelled) {
                // The answer came
            }
        });
        try {
            $this->waiters[$id]->await();
        } finally {
            $this->waiters[$id]->cancel();
            unset($this->waiters[$id]);
        }
    }

    /** @param list<mixed> $args */
    private static function call(string $function, array $args, ?int $id = null): string
    {
        return json_encode(['t' => 'js', 'f' => $function, 'a' => $args] + ($id === null ? [] : ['r' => $id]), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
