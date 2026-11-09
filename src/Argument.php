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
        $type = $parameter->getType();
        // An empty field for a nullable parameter that takes no empty string: nothing chosen, also for a ?bool,
        // which would otherwise read it as false
        if ($value === '' && $type !== null && $type->allowsNull() && preg_match('/\b(string|mixed)\b/', (string) $type) !== 1) {
            return [true, null];
        }
        [$fits, $converted] = Hydrator::fitting($type, $value);
        return [$fits, $converted];
    }
}
