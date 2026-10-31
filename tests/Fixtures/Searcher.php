<?php

namespace Io\Live\Tests\Fixtures;

use Io\Live\{Component, Input};

/** Starts from the page's query string: ?q=, ?p= and ?tag= */
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

    /** @var list<string> */
    #[Input(query: 'tag')]
    public array $tags = [];

    public function search(string $q): void
    {
        $this->q = $q;
        $this->page = 1;
    }

    public function tag(string $tag): void
    {
        $this->tags[] = $tag;
    }

    public function next(): void
    {
        $this->page++;
    }
}
