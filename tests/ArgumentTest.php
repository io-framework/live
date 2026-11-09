<?php

namespace Io\Live\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use Io\Live\Argument;

final class ArgumentTest extends TestCase
{
    /** @return array{bool, mixed} */
    private static function convert(\Closure $takes, mixed $value): array
    {
        return Argument::convert(new ReflectionFunction($takes)->getParameters()[0], $value);
    }

    public function testAnEmptyValueIsNothingChosenForEveryNullableTypeButText(): void
    {
        $this->assertSame([true, null], self::convert(static fn (?bool $v) => $v, ''));
        $this->assertSame([true, null], self::convert(static fn (?int $v) => $v, ''));
        $this->assertSame([true, null], self::convert(static fn (int|bool|null $v) => $v, ''));
        $this->assertSame([true, ''], self::convert(static fn (?string $v) => $v, ''));
        $this->assertSame([true, ''], self::convert(static fn (mixed $v) => $v, ''));
        $this->assertSame([true, ''], self::convert(static fn ($v) => $v, ''));
    }

    public function testAnEmptyValueStaysFalseForAPlainBoolAndAOtherValueConverts(): void
    {
        $this->assertSame([true, false], self::convert(static fn (bool $v) => $v, ''));
        $this->assertSame([true, true], self::convert(static fn (?bool $v) => $v, 'on'));
        $this->assertSame([false, null], self::convert(static fn (int $v) => $v, ''));
    }
}
