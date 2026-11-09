---
title: "JsInvokable"
description: "A public method of a component that the page's JavaScript may call: `live.invoke(element, 'method', ...args)`."
order: 100
generated: true
---

`Io\Live\JsInvokable` · final class · namespace [`Io\Live`](index.md)

```php
final class JsInvokable
```

A public method of a component that the page's JavaScript may call: `live.invoke(element, 'method', ...args)`.
Any browser can call it with any values, whatever the template shows: the values are converted to the
parameters' types, its #[Role] or #[Permissions] hold as for a click, the rest is up to the method.
