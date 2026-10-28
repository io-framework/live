<?php

namespace Io\Live;

use Closure;
use Io\View\{Check, Checks};

/**
 * The components' templates in `vendor/bin/io-check` (composer.json names it under
 * extra.io.checks): what Problems finds, and each template as the PHP PHPStan reads, with `$this` as its
 * component, private members included.
 */
final class TemplateChecks implements Checks
{
    private ?Components $components = null;

    public function __construct(private readonly string $root)
    {
    }

    /**
     * @internal What PHPStan reads in a generated file: $template runs with `$this` as a $class, private members included,
     * as a template does.
     *
     * @template T of object
     * @param class-string<T> $class
     * @param Closure(Closure(string, array<string, mixed>): string, Closure(string, list<mixed>|null=): string, Closure(string): (callable(mixed...): mixed)): void $template
     * @param-closure-this T $template
     */
    public static function bind(string $class, Closure $template): void
    {
    }

    public function problems(): array
    {
        $problems = $this->components()->check();
        $helpers = Check::helperNames($this->root);
        if ($helpers === null) {
            return $problems;
        }
        // $this->name() without such a method is a view helper: one of the installed plugins must offer it
        $components = $this->components();
        $broken = array_column(array_filter($problems, static fn (array $p) => $p['severity'] === 'error'), 'file', 'file');
        foreach ($components->sources() as [$class, $path]) {
            $source = (string) @file_get_contents($path);
            if (isset($broken[$path])) {
                continue;
            }
            $php = Template::compile($source, $components->selectors(), class: $class);
            preg_match_all("/\\\$__helper\\('(\\w+)'\\)/", $php, $calls, PREG_OFFSET_CAPTURE);
            foreach ($calls[1] as [$name, $offset]) {
                if (!in_array($name, $helpers, true)) {
                    $close = array_values(array_filter($helpers, static fn (string $h) => levenshtein(strtolower($name), strtolower($h)) <= 2));
                    $problems[] = [
                        'file' => $path,
                        'line' => substr_count($php, "\n", 0, $offset) + 1,
                        'message' => Check::unknownHelper($name, $close),
                        'severity' => 'error',
                    ];
                }
            }
        }
        return $problems;
    }

    public function generate(string $directory): array
    {
        $components = $this->components();
        $broken = array_column(array_filter($components->check(), static fn (array $p) => $p['severity'] === 'error'), 'file', 'file');
        $templates = [];
        foreach ($components->sources() as $selector => [$class, $path]) {
            $source = file_get_contents($path);
            if ($source === false || isset($broken[$path])) {
                continue;
            }
            // Line for line: the compiled template leaves PHP on the first line, after its namespace and imports and the
            // bind. The closing tag at the end ends a template that ends in PHP, and is only text after one that ends in HTML
            $generated = "$directory/$selector.php";
            $bind = '\\' . self::class . '::bind(\\' . $class . '::class, function ($__child, $__action, $__helper): void { ';
            file_put_contents($generated, '<?php ' . Template::compile($source, $components->selectors(), class: $class, first: $bind) . "\n?><?php });\n");
            $templates[$generated] = $path;
        }
        return $templates;
    }

    private function components(): Components
    {
        return $this->components ??= Components::scan($this->root);
    }
}
