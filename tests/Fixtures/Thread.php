<?php

namespace Io\Live\Tests\Fixtures;

use Io\Attributes\Inject;
use Io\Live\{Component, Input, Topics};

/** The room's messages under one thread: it renders for a broadcast of its own topic only. */
#[Component(
    selector: 'test-thread',
    template: 'Thread.Template.php'
)]
final class Thread
{
    #[Input]
    public int $thread = 0;

    #[Inject]
    public Room $room;

    /** @return list<string> */
    #[Topics]
    public function topics(): array
    {
        return ["thread:$this->thread"];
    }
}
