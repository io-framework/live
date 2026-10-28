<?php

namespace Io\Live\Tests\Fixtures;

use Io\Live\Component;

/** Calls a view helper of another plugin as $this->shout(), next to a method of its own */
#[Component(
    selector: 'test-greeter',
    template: 'Greeter.Template.php'
)]
final class Greeter
{
    public function name(): string
    {
        return 'mia';
    }
}
