<?php

namespace Io\Live;

use WeakMap;

/**
 * Renders every connected tab again: for state that tabs share through an app-wide service, such as a chat. A
 * tab only renders after its own actions and timers; `$broadcast->changed()` after a change reaches the others.
 * Inject it into a component or a service, or take it with `$app->get(Broadcast::class)`.
 *
 * With topics, `$broadcast->changed("comments:$anime")` renders only the components whose #[Topics] method names
 * one of them, and leaves every other tab alone.
 *
 * Under a Tenma with Messaging\Channel::publish(), the other workers render their tabs too, and with `tenma --pubsub` every
 * process on that Redis.
 */
final class Broadcast
{
    private const CHANNEL = 'io-live';

    /** @var WeakMap<Circuit, true> The circuits whose browser is connected. */
    private WeakMap $circuits;

    /** Whether this worker listens to the other workers' broadcasts yet */
    private bool $subscribed = false;

    /** @internal LivePlugin creates the one instance of the app */
    public function __construct()
    {
        $this->circuits = new WeakMap();
    }

    /**
     * Every connected tab renders, and sends the components whose HTML changed. With $topics only the top-level
     * components that hold a component whose #[Topics] method names one of them.
     */
    public function changed(string ...$topics): void
    {
        if (self::acrossWorkers()) {
            $message = json_encode(array_values($topics), JSON_THROW_ON_ERROR);
            new \Messaging\Channel(self::CHANNEL)->publish($message);
        } else {
            $this->changedHere(...$topics);
        }
    }

    /** Outside Tenma (tests) or under a Tenma without publish(): only this worker's tabs. */
    private static function acrossWorkers(): bool
    {
        // Tenma's own function behind Messaging\Channel::publish(), there since publish() is
        return function_exists('tenma_pubsub');
    }

    /**
     * Only this worker's tabs render: in a Messaging\Channel::subscribe() callback that has just brought this worker's state
     * up to date. Every worker runs it, so changed() would render every tab once per worker. $topics as in changed().
     */
    public function changedHere(string ...$topics): void
    {
        $topics = array_values($topics);
        // A render may wait on a query while a tab connects or leaves: go through the tabs as they were
        $circuits = [];
        foreach ($this->circuits as $circuit => $_) {
            $circuits[] = $circuit;
        }
        foreach ($circuits as $circuit) {
            $circuit->changed(cause: 'a broadcast', topics: $topics);
        }
    }

    /** A broadcast from any worker: the topics as JSON, an empty message from an older worker for every tab. */
    private function received(string $message): void
    {
        $topics = $message === '' ? [] : json_decode($message, true);
        if (!is_array($topics)) {
            $topics = [];
        }
        $this->changedHere(...array_filter($topics, is_string(...)));
    }

    /** @internal In dev: a saved template that does not compile, or null once it does again, to this worker's tabs. */
    public function problem(string $file, ?\Throwable $e): void
    {
        foreach ($this->circuits as $circuit => $_) {
            $circuit->problem($file, $e);
        }
    }

    /** @internal */
    public function connect(Circuit $circuit): void
    {
        // Once a tab connects: a subscription keeps the process running, which a script without a server must not.
        // Detached, it belongs to no request: one made in this tab's request would end when the tab closes.
        if (!$this->subscribed && self::acrossWorkers()) {
            $this->subscribed = true;
            \Threading\Task::detach(fn () => new \Messaging\Channel(self::CHANNEL)->subscribe($this->received(...)));
        }
        $this->circuits[$circuit] = true;
    }

    /** @internal */
    public function disconnect(Circuit $circuit): void
    {
        unset($this->circuits[$circuit]);
    }
}
