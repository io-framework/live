<?php

namespace Io\Live;

use Attribute;

/**
 * Marks the method that names the topics a component renders for: `$broadcast->changed('comments:7')` renders the
 * tabs whose components name that topic, and in them only the top-level components that hold one.
 *
 *     #[Topics]
 *     public function topics(): array { return ["comments:$this->anime"]; }
 *
 * The method runs when a broadcast with topics arrives, so it follows the component's inputs. A component without
 * it renders only for a broadcast without topics.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Topics
{
}
