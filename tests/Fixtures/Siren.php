<?php

namespace Io\Live\Tests\Fixtures;

use Io\Attributes\{Authenticated, Role};
use Io\Live\{Component, Every};

/** A timer only admins may run */
#[Component(
    selector: 'test-siren',
    template: 'Siren.Template.php'
)]
#[Authenticated]
final class Siren
{
    public int $count = 0;

    #[Every(1.0)]
    #[Role(
        role: 'admin'
    )]
    public function sound(): void
    {
        $this->count++;
    }
}
