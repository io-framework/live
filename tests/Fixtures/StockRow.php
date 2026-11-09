<?php

namespace Io\Live\Tests\Fixtures;

/** A plain row: every render loads it again */
final class StockRow
{
    public function __construct(public int $id, public string $name)
    {
    }
}
