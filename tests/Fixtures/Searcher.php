<?php

namespace Io\Live\Tests\Fixtures;

use Io\Live\{Component, Input};

/** Starts from the page's query string: ?q= and ?p= */
#[Component(
    selector: 'test-searcher',
    template: 'Searcher.Template.php'
)]
final class Searcher
{
    #[Input(query: true)]
    public string $q = '';

    #[Input(query: 'p')]
    public int $page = 1;
}
