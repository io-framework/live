<?php

namespace Io\Live\Tests\Fixtures;

use Io\Live\Component;

/** Rows loaded fresh on every render, bound to a call */
#[Component(
    selector: 'test-stock',
    template: 'Stock.Template.php'
)]
final class Stock
{
    /** @var array<int, string> */
    public array $names = [1 => 'tea', 2 => 'coffee'];

    /** @return list<StockRow> */
    public function rows(): array
    {
        $rows = [];
        foreach ($this->names as $id => $name) {
            $rows[] = new StockRow($id, $name);
        }
        return $rows;
    }

    public function remove(StockRow $row): void
    {
        unset($this->names[$row->id]);
    }
}
