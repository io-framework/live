<?php

namespace Io\Live;

use ReflectionParameter;
use Io\Hydrator;

/**
 * @internal A value from the browser as the action's parameter takes it, by the rules #[Body] DTOs follow
 * (the Hydrator): form text as a number or a boolean ("7", "2.5", "on", "false"), a backed enum case by its value,
 * a DTO from the form's array. Never cast: "abc" is no int, "7.5" no int.
 */
final class Argument
{
    /** @return array{bool, mixed} Whether the value fits, and the value the parameter gets */
    public static function convert(ReflectionParameter $parameter, mixed $value): array
    {
        [$fits, $converted] = Hydrator::fitting($parameter->getType(), $value);
        // An empty field for a nullable parameter that takes no empty string: nothing chosen
        if (!$fits && $value === '' && $parameter->allowsNull()) {
            return [true, null];
        }
        return [$fits, $converted];
    }
}
