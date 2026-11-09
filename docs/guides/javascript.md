---
title: JavaScript
description: Call the browser's functions from a component with Js, and call #[JsInvokable] methods from the page's scripts.
order: 75
---

Most components need no JavaScript. For what only the browser has, such as its storage, the clipboard or a chart library, a component talks to the page's scripts over the same WebSocket as everything else.

## From PHP to the browser

A component or a `#[Scoped]` service injects [`Js`](../reference/Io/Live/Js.md). It calls a function on `window` by its path, with the object in front as `this`:

```php
#[Inject]
private Js $js;

public string $theme = 'light';

public function load(): void
{
    $this->theme = (string) $this->js->invoke('localStorage.getItem', 'theme');
}

public function save(array $points): void
{
    $this->points = $points;
    $this->js->invokeVoid('bakery.chart.draw', $points);
}
```

- `invoke()` waits for what the function returned, a promise's value included. `undefined` comes back as `null`. Only this action waits: other tabs carry on, and the tab's next events wait behind it.
- `invokeVoid()` waits for nothing. The call goes out once the action is done, behind its render, so the function finds the new HTML on the page. A failure shows in the browser's console.

Arguments and results travel as JSON. `invoke()` throws [`JsException`](../reference/Io/Live/JsException.md) when the function throws, when no browser is connected, when the connection drops meanwhile, and when no answer comes within a minute.

The page's first render has no browser yet: `invoke()` throws and `invokeVoid()` is dropped. The component mounts again once the browser connects, and its calls reach it there. `$js->connected` tells the two apart, for a component that asks during its lifecycle:

```php
if ($this->js->connected) {
    $this->theme = (string) $this->js->invoke('localStorage.getItem', 'theme');
}
```

An element the function changes on its own needs `data-live-ignore`, or the next render puts it back ([Events and actions](events.md)).

## From the browser to PHP

A public method with `#[JsInvokable]` can be called from the page's scripts:

```php
#[JsInvokable]
public function resize(int $width): string
{
    $this->width = $width;
    return "{$width}px";
}
```

```js
const size = await live.invoke(document.querySelector('#chart'), 'resize', 640);
```

`live.invoke()` takes an element, and calls the method on the component around it. It goes out behind what was typed and clicked before. The promise gets the method's return value once what the method changed is on the page. It is rejected without a connection, also when the page has only just loaded, when the connection drops before the answer, and when the method fails or is refused. As with any action, details stay in the server's log.

The method runs like an action: everything renders afterwards, and an exception is logged and marks the component with `data-live-error`.

## What to keep in mind

- What `invoke()` returns comes from the browser: it is input like a form field, and can be anything.
- A `#[JsInvokable]` method can be called by any browser with any values, whether the page's scripts do so or not. Only marked methods are reachable, the values are converted to the parameters' types as with any action, and `#[Role]` and `#[Permissions]` hold. Which record the caller may touch is for the method to check.
- The function's path runs as it is. Never build it from a user's input.

## In a test

[`Testing\Tab`](testing.md) answers for the browser: `answer()` says what a function returns, or takes a Closure, which may throw. Any other function returns `null`.

```php
$tab = Tab::for($app)->answer('localStorage.getItem', 'dark');
$theme = $tab->mount('app-theme');

$theme->click('load')->assertSee('dark');
$tab->assertInvoked('localStorage.getItem', 'theme');

$this->assertSame('640px', $theme->invoke('resize', 640));
```

See also: [`Js`](../reference/Io/Live/Js.md), [`JsInvokable`](../reference/Io/Live/JsInvokable.md), [Security model](security.md).
