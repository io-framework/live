<?php

namespace Io\Live\Tests\Fixtures;

use Io\Live\Component;

/** Shows its wipe button only while open */
#[Component(
    selector: 'test-safe',
    template: 'Safe.Template.php'
)]
final class Safe
{
    public bool $open = false;

    public int $wiped = 0;

    public function toggle(): void
    {
        $this->open = !$this->open;
    }

    public function wipe(): void
    {
        $this->wiped++;
    }
}
