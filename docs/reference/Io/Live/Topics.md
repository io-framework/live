---
title: "Topics"
description: "Marks the method that names the topics a component renders for: `$broadcast->changed('comments:7')` renders the tabs whose components name that topic, and in them only the top-level components that hold one."
order: 100
generated: true
---

`Io\Live\Topics` · final class · namespace [`Io\Live`](index.md)

```php
final class Topics
```

Marks the method that names the topics a component renders for: `$broadcast->changed('comments:7')` renders the
tabs whose components name that topic, and in them only the top-level components that hold one.

    #[Topics]
    public function topics(): array { return ["comments:$this->anime"]; }

The method runs when a broadcast with topics arrives, so it follows the component's inputs. A component without
it renders only for a broadcast without topics.
