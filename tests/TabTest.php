<?php

namespace Io\Live\Tests;

use PHPUnit\Framework\TestCase;
use Io\App;
use Io\Live\LivePlugin;
use Io\Live\Testing\Tab;
use Io\Live\Tests\Fixtures\{Basket, BasketBadge, Board, Counter, Badged, Greeter, Item, Searcher, ItemList, Door, Shop, ShoutPlugin, Uploader};

final class TabTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        App::reset();
        $this->app = App::create();
        $this->app->plugin(new LivePlugin([Counter::class, ItemList::class, Item::class, Shop::class, Uploader::class, Door::class, BasketBadge::class, Board::class, Greeter::class, Badged::class, Searcher::class]));
        $this->app->plugin(new ShoutPlugin());
        $this->app->configure(fn (\Io\Options\RendererOptions $views) => $views->directory = __DIR__ . '/Fixtures/views');
        $this->app->attach([]);
    }

    protected function tearDown(): void
    {
        App::reset();
    }

    public function testTheWebSocketRouteIsNoPartOfTheApi(): void
    {
        $spec = (new \Io\OpenAPI\OpenAPIGenerator($this->app->getRouter()))->generate();
        $this->assertArrayNotHasKey('/_live', $spec['paths'] ?? []);
    }

    public function testClicksGoThroughTheTemplateAndTimersRunOnFakeTime(): void
    {
        $tab = Tab::for($this->app);
        $counter = $tab->mount('test-counter', ['count' => 1]);
        $counter->click('add')->assertSee('2')->assertDontSee('admin');
        $this->assertSame(2, $counter->component()->count);
        $counter->click('button')->assertSee('3');
        $tab->advance(2.5);
        $this->assertSame(5, $counter->component()->count);
        // The template escapes what it prints: assertSee looks for the escaped text
        $counter->assertSee('<b>');
    }

    public function testATemplateCallsAnotherPluginsViewHelperThroughThis(): void
    {
        $greeter = Tab::for($this->app)->mount('test-greeter');

        $this->assertStringContainsString('<p>HELLO MIA</p>', $greeter->html());
    }

    public function testATemplateIncludesAViewOfTheApp(): void
    {
        $badged = Tab::for($this->app)->mount('test-badged');

        $this->assertStringContainsString('<div><span class="badge">&lt;new&gt;</span>', $badged->html());
    }

    public function testQueryInputsStartFromThePagesQueryString(): void
    {
        $page = fn (string $uri, array $inputs = []): string => Tab::page($this->app, 'test-searcher', $inputs, \Io\Http\Request::fromParts('GET', $uri));

        $this->assertStringContainsString('<p>pretzel|3</p>', $page('/s?q=pretzel&p=3'));
        // A value that does not fit leaves the default; the tag's own value wins over the query
        $this->assertStringContainsString('<p>pretzel|1</p>', $page('/s?q=pretzel&p=three'));
        $this->assertStringContainsString('<p>tag|3</p>', $page('/s?q=pretzel&p=3', ['q' => 'tag']));
        // It goes into data-inputs, so the component keeps it when the browser connects
        $this->assertStringContainsString('&quot;page&quot;:3', $page('/s?p=3'));
    }

    public function testAClickTogglesAConditionalClass(): void
    {
        $board = Tab::for($this->app)->mount('test-board');
        $this->assertStringContainsString('class="btn phase-[open]:ring">', $board->html());
        $this->assertStringContainsString('class="btn done phase-[open]:ring">', $board->click('button')->html());
        $this->assertStringContainsString('class="btn phase-[open]:ring">', $board->click('button')->html());
    }

    public function testAMethodTheTemplateDoesNotWireFails(): void
    {
        $counter = Tab::for($this->app)->mount('test-counter');
        $this->expectExceptionMessage('Nothing in <c0> has data-on-click="reset"');
        $counter->click('reset');
    }

    public function testChildrenBoundArgumentsFilesAndNavigation(): void
    {
        $tab = Tab::for($this->app);
        $list = $tab->mount('test-list');
        $list->find('test-item', 'b')->click('choose')->assertSee('b 1');
        $list->assertSee('picked b');

        $shop = $tab->mount('test-shop');
        $shop->click('remove')->assertDontSee('tea')->assertSee('coffee');
        $shop->input('input', 'cocoa')->assertSee('cocoa');

        $tab->mount('test-uploader')->upload('input', 'a.txt', 'hello', 'text/plain')->assertSee('a.txt text/plain 5 hello')
            ->submit('form', ['title' => 'Trip'])->assertSee('Trip:');

        $tab->mount('app-door')->click('leave');
        $tab->assertNavigatedTo('/elsewhere');
    }

    public function testAFakeThatExtendsAScopedServiceTakesItsPlace(): void
    {
        $tab = Tab::for($this->app)->with(new class extends Basket {
            public int $items = 7;
        });
        $tab->mount('test-basket-badge')->assertSee('7');

        $this->expectExceptionMessage('is no #[Scoped] service');
        $tab->with(new \stdClass());
    }

    public function testAnErrorFailsTheTestWithItsOwnStack(): void
    {
        $this->expectExceptionMessage('the door is stuck');
        Tab::for($this->app)->mount('app-door')->click('jam');
    }

    public function testThePageRendersWithoutAConnection(): void
    {
        $this->assertStringStartsWith('<test-counter data-live data-inputs="{&quot;count&quot;:7}"><button', Tab::page($this->app, 'test-counter', ['count' => 7]));
    }
}
