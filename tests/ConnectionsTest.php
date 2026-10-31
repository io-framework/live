<?php

namespace Io\Live\Tests;

use Io\Live\Connections;
use Io\SignOuts;
use PHPUnit\Framework\TestCase;

final class ConnectionsTest extends TestCase
{
    protected function tearDown(): void
    {
        Connections::reset();
        SignOuts::reset();
    }

    public function testAnEndedSignInClosesItsConnectionsOnly(): void
    {
        $closed = [];
        Connections::opened(['sub' => 1, 'sid' => '7'], 1, function () use (&$closed): void {
            $closed[] = 'first tab';
        });
        Connections::opened(['sub' => 1, 'sid' => '7'], 2, function () use (&$closed): void {
            $closed[] = 'second tab';
        });
        Connections::opened(['sub' => 1, 'sid' => '8'], 3, function () use (&$closed): void {
            $closed[] = 'other browser';
        });
        // A JWT without a sign-in and nobody are not kept
        Connections::opened(['sub' => 2], 4, function () use (&$closed): void {
            $closed[] = 'jwt';
        });
        Connections::opened(null, 5, function () use (&$closed): void {
            $closed[] = 'nobody';
        });
        Connections::closed(['sub' => 1, 'sid' => '7'], 2);

        SignOuts::publish(['7', '9']);

        $this->assertSame(['first tab'], $closed);
    }
}
