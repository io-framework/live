---
title: "Js"
description: "The browser's JavaScript, for a component or a #[Scoped] service that injects it: calls a function on `window` by its path (\"localStorage.getItem\", \"app.chart.draw\")."
order: 100
generated: true
---

`Io\Live\Js` · final class · namespace [`Io\Live`](index.md)

```php
final class Js
```

The browser's JavaScript, for a component or a #[Scoped] service that injects it: calls a function on `window`
by its path ("localStorage.getItem", "app.chart.draw"). The arguments and the result travel as JSON.

What the browser answers is input like a form field: it can answer anything. The path runs as it is, so it never
comes from a user.

## Properties

### $connected

```php
public private(set) bool $connected = true;
```

Whether a browser listens. False during the page's first render and while the connection is away.

## Methods

### invoke()

```php
public function invoke(string $function, mixed ...$args): mixed
```

Calls the function and returns what it returned, a promise's value included; undefined comes back as null.
Only this fiber waits, the tab's next events wait with it. The page still shows the last render: to reach
what this action renders, use invokeVoid().

**Parameters**

- `$function` `string`
- `...$args` `mixed`

**Returns** `mixed`

**Throws**

- [`JsException`](JsException.md): The function threw, no browser is connected, or no answer came within a minute

### invokeVoid()

```php
public function invokeVoid(string $function, mixed ...$args): void
```

Calls the function once the action is done and its render is on the page, without waiting for it. A failure
shows in the browser's console. Without a browser it is dropped: a component that asks during the page's first
render mounts again when the browser connects, and asks again there.

**Parameters**

- `$function` `string`
- `...$args` `mixed`
