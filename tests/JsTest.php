<?php

namespace Io\Live\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Throwable;
use Io\Container;
use Io\Environment;
use Io\Logger;
use Io\Live\{Circuit, Js, JsException, LiveOptions, LivePlugin};
use Io\Live\Tests\Fixtures\Bridge;

final class JsTest extends TestCase
{
    /** @var list<array<string, mixed>> */
    private array $sent = [];

    /** @var list<Throwable> */
    private array $reported = [];

    private ?Circuit $circuit = null;

    /** @var array<string, mixed> The next answer of the browser, as it sends it */
    private array $answer = ['v' => 'dark'];

    private function plugin(): LivePlugin
    {
        $live = new LivePlugin([Bridge::class]);
        $live->boot(new Container(), new class extends Environment {
            public function __construct()
            {
            }

            public function isDev(): bool
            {
                return false;
            }
        }, new Logger(), new LiveOptions());
        return $live;
    }

    private function mounted(): Circuit
    {
        $this->circuit = new Circuit(
            $this->plugin(),
            function (string $message): void {
                $message = json_decode($message, true);
                $this->sent[] = $message;
                if (($message['t'] ?? null) === 'js' && isset($message['r'])) {
                    $this->circuit?->handle(json_encode(['t' => 'answer', 'r' => $message['r'], ...$this->answer], JSON_THROW_ON_ERROR));
                }
            },
            function (Throwable $e): void {
                $this->reported[] = $e;
            },
            static fn (): \Closure => static fn () => null,
        );
        $this->handle(['t' => 'mount', 'id' => 'c0', 'c' => 'test-bridge', 'p' => (object) []]);
        $this->sent = [];
        return $this->circuit;
    }

    /** @param array<string, mixed> $message */
    private function handle(array $message): void
    {
        ($this->circuit ?? throw new LogicException('no circuit'))->handle(json_encode($message, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function only(string $type): array
    {
        $found = array_values(array_filter($this->sent, static fn (array $m) => ($m['t'] ?? null) === $type));
        $this->assertCount(1, $found);
        return $found[0];
    }

    public function testInvokeGivesTheActionWhatTheBrowserAnswered(): void
    {
        $this->mounted();
        $this->handle(['t' => 'call', 'id' => 'c0', 'm' => 'read', 'a' => []]);

        $call = $this->only('js');
        $this->assertSame(['localStorage.getItem', ['theme']], [$call['f'], $call['a']]);
        $this->assertStringContainsString('<p>dark</p>', (string) end($this->sent)['html']);
    }

    public function testAFunctionThatThrowsThrowsInTheAction(): void
    {
        $this->mounted();
        $this->answer = ['e' => 'quota exceeded'];
        $this->handle(['t' => 'call', 'id' => 'c0', 'm' => 'read', 'a' => []]);

        $this->assertStringContainsString('localStorage.getItem(): quota exceeded', (string) end($this->sent)['html']);
    }

    public function testAnAnswerNobodyAskedForIsIgnored(): void
    {
        $this->mounted();
        $this->handle(['t' => 'answer', 'r' => 99, 'v' => 'x']);
        $this->handle(['t' => 'answer', 'r' => 'nonsense']);

        $this->assertSame([], $this->sent);
        $this->assertSame([], $this->reported);
    }

    public function testInvokeVoidRunsBehindTheRenderOfItsAction(): void
    {
        $this->mounted();
        $this->handle(['t' => 'call', 'id' => 'c0', 'm' => 'draw', 'a' => []]);

        $this->assertStringContainsString('drawn', (string) $this->sent[0]['html']);
        $this->assertSame(['t' => 'js', 'f' => 'chart.draw', 'a' => [1, 2]], $this->sent[1]);
    }

    public function testWithoutABrowserInvokeFailsAndInvokeVoidIsDropped(): void
    {
        $circuit = $this->mounted();
        $js = $circuit->services()->get(Js::class);
        $circuit->detach();

        $js->invokeVoid('chart.draw');
        $js->flush();
        $this->assertSame([], $this->sent);
        $this->assertFalse($js->connected);
        $this->expectException(JsException::class);
        $js->invoke('chart.draw');
    }

    public function testThePagesFirstRenderHasNoBrowser(): void
    {
        $html = Circuit::page($this->plugin(), 'test-bridge', []);

        $this->assertStringContainsString('nothing', $html);
    }

    public function testJavaScriptCallsAMarkedMethodAndGetsItsValueBehindTheRender(): void
    {
        $this->mounted();
        $this->handle(['t' => 'invoke', 'id' => 'c0', 'm' => 'resize', 'a' => ['640', 'em'], 'r' => 7]);

        $this->assertStringContainsString('640em', (string) $this->sent[0]['html']);
        $this->assertSame(['t' => 'return', 'r' => 7, 'v' => '640em'], $this->only('return'));
    }

    /** @return iterable<string, array{string, list<mixed>}> */
    public static function refused(): iterable
    {
        yield 'a public method without the attribute' => ['secret', []];
        yield 'a method that does not exist' => ['nothing', []];
        yield 'a value its type does not take' => ['resize', ['wide']];
        yield 'more values than it has parameters' => ['resize', [1, 'px', 'more']];
        yield 'too few values' => ['resize', []];
        yield 'a value JSON cannot carry' => ['unsendable', []];
    }

    /** @param list<mixed> $args */
    #[\PHPUnit\Framework\Attributes\DataProvider('refused')]
    public function testARefusedCallIsAnsweredWithAnError(string $method, array $args): void
    {
        $this->mounted();
        $this->handle(['t' => 'invoke', 'id' => 'c0', 'm' => $method, 'a' => $args, 'r' => 7]);

        $this->assertSame(['t' => 'return', 'r' => 7, 'error' => true], $this->only('return'));
        $this->assertCount(1, $this->reported);
    }
}
