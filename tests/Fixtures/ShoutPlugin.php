<?php

namespace Io\Live\Tests\Fixtures;

use Io\Plugin\{PluginBase, ViewHelper};

/** A plugin that offers one view helper, as I18n offers t() */
final class ShoutPlugin extends PluginBase implements ViewHelper
{
    public function name(): string
    {
        return 'shout';
    }

    public function hooks(): array
    {
        return [$this];
    }

    public function helpers(): array
    {
        return ['shout' => fn (string $text): string => strtoupper($text)];
    }
}
