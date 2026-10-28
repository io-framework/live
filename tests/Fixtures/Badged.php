<?php

namespace Io\Live\Tests\Fixtures;

use Io\Live\Component;

/** Includes a view of the app with @include, as a page does */
#[Component(
    selector: 'test-badged',
    template: 'Badged.Template.php'
)]
final class Badged
{
    public function label(): string
    {
        return '<new>';
    }
}
