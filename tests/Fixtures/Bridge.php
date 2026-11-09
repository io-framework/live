<?php

namespace Io\Live\Tests\Fixtures;

use Io\Attributes\Inject;
use Io\Live\{Component, Js, JsException, JsInvokable};

#[Component(
    selector: 'test-bridge',
    template: 'Bridge.Template.php'
)]
final class Bridge
{
    #[Inject]
    private Js $js;

    public string $shown = 'nothing';

    public function read(): void
    {
        try {
            $this->shown = (string) $this->js->invoke('localStorage.getItem', 'theme');
        } catch (JsException $e) {
            $this->shown = $e->getMessage();
        }
    }

    public function draw(): void
    {
        $this->shown = 'drawn';
        $this->js->invokeVoid('chart.draw', 1, 2);
    }

    #[JsInvokable]
    public function resize(int $width, string $unit = 'px'): string
    {
        $this->shown = "$width$unit";
        return $this->shown;
    }

    /** Public, but not marked. */
    public function secret(): string
    {
        return 'secret';
    }

    #[JsInvokable]
    public function unsendable(): float
    {
        return NAN;
    }
}
