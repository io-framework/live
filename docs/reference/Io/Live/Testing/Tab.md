---
title: "Tab"
description: "One browser tab in a test, without a browser: the app's components with its services, driven through the same messages the browser sends, so a test proves the template's wiring too."
order: 100
generated: true
---

`Io\Live\Testing\Tab` · final class · namespace [`Io\Live\Testing`](index.md)

```php
final class Tab
```

One browser tab in a test, without a browser: the app's components with its services, driven through the same
messages the browser sends, so a test proves the template's wiring too.

    $tab = Tab::for($app);
    $button = $tab->mount('app-add-to-cart', ['product' => 3]);
    $button->click('add')->assertSee('1 in your cart');
    $tab->advance(1.0);                              // #[Every] timers

An error the browser would only see as `data-live-error` fails the test with its own stack. Two tabs of one app
share its services, so a Broadcast reaches the other.

## Methods

### for()

```php
public static function for(\Io\App $app, ?\Io\Http\Request $request = null): \Io\Live\Testing\Tab
```

A tab of $app, once attach() has booted its plugins; $request is the connection's (cookies, the user).

**Parameters**

- `$app` `\Io\App`
- `$request` `?\Io\Http\Request`

**Returns** [`Tab`](Tab.md)

### page()

```php
public static function page(
    \Io\App $app,
    string $selector,
    array $inputs = [],
    ?\Io\Http\Request $request = null,
): string
```

The HTML a page shows before any connection: for what users without JavaScript see.

**Parameters**

- `$app` `\Io\App`
- `$selector` `string`
- `$inputs` `array<string, mixed>`
- `$request` `?\Io\Http\Request`

**Returns** `string`

### with()

```php
public function with(object $service): \Io\Live\Testing\Tab
```

Puts a service, such as a fake, into this tab before its components are created, for the #[Scoped] class it is or extends.

**Parameters**

- `$service` `object`

**Returns** [`Tab`](Tab.md)

### mount()

```php
public function mount(string $selector, array $inputs = []): \Io\Live\Testing\Mounted
```

Places a top-level component as the browser does when it connects, and renders it.

**Parameters**

- `$selector` `string`
- `$inputs` `array<string, mixed>`: As the page passes them

**Returns** [`Mounted`](Mounted.md)

### answer()

```php
public function answer(string $function, mixed $value): \Io\Live\Testing\Tab
```

What a function returns when a component calls it through Js::invoke(), or the Closure that answers its
arguments; a Closure that throws is a function that fails. Any other function returns null.

**Parameters**

- `$function` `string`
- `$value` `mixed`

**Returns** [`Tab`](Tab.md)

### assertInvoked()

```php
public function assertInvoked(string $function, mixed ...$args): \Io\Live\Testing\Tab
```

That a component called $function through Js, with these arguments.

**Parameters**

- `$function` `string`
- `...$args` `mixed`

**Returns** [`Tab`](Tab.md)

### advance()

```php
public function advance(float $seconds): \Io\Live\Testing\Tab
```

Lets $seconds pass: the #[Every] timers that are due run, in order.

**Parameters**

- `$seconds` `float`

**Returns** [`Tab`](Tab.md)

### assertQuery()

```php
public function assertQuery(array $query): \Io\Live\Testing\Tab
```

That the browser's address shows these #[Input(query: ...)] values, by query key; null for one it leaves out

**Parameters**

- `$query` `array<string, mixed>`

**Returns** [`Tab`](Tab.md)

### assertNavigatedTo()

```php
public function assertNavigatedTo(string $url): \Io\Live\Testing\Tab
```

That the last navigate() of an action or timer in this tab asked for $url.

**Parameters**

- `$url` `string`

**Returns** [`Tab`](Tab.md)
