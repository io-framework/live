<?php

namespace Io\Live;

/**
 * Live's options, set with `$app->configure(fn (LiveOptions $live) => $live->editor = '...')`.
 */
class LiveOptions
{
    /**
     * In dev, where a file in an error opens: `{file}` and `{line}` are filled in. VSCodium:
     * `vscodium://file/{file}:{line}`, PhpStorm: `phpstorm://open?file={file}&line={line}`.
     */
    public string $editor = 'vscode://file/{file}:{line}';

    /**
     * How many top-level components one tab may mount. The browser asks for each, so this bounds what one
     * tab costs the worker; children placed by a template don't count.
     */
    public int $maxComponents = 50;
}
