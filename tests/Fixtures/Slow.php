<?php

namespace Io\Live\Tests\Fixtures;

use Fiber;
use Io\Live\{Component, Every};

/** A component that waits on I/O in the middle of its render, whenever a test lets it */
#[Component(
    selector: 'test-slow',
    template: 'Slow.Template.php'
)]
final class Slow
{
    public static bool $waits = false;

    public int $count = 0;

    public int $renders = 0;

    public int $ticks = 0;

    public function add(): void
    {
        $this->count++;
    }

    /** What the template shows, read before the wait as a query's parameters would be */
    public function shown(): string
    {
        $shown = $this->count . '/' . ++$this->renders;
        self::wait();
        return $shown;
    }

    #[Every(1.0)]
    public function tick(): void
    {
        $this->ticks++;
        self::wait();
    }

    private static function wait(): void
    {
        if (self::$waits) {
            Fiber::suspend();
        }
    }
}
