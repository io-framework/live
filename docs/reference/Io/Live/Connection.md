---
title: "Connection"
description: "The browser connection of a circuit, for a component or a #[Scoped] service that injects it: the request the WebSocket came in with (its cookies, and $request->principal as the app's middlewares left it), and a way to send the browser to another page."
order: 100
generated: true
---

`Io\Live\Connection` · final class · namespace [`Io\Live`](index.md)

```php
final class Connection
```

The browser connection of a circuit, for a component or a #[Scoped] service that injects it: the request the
WebSocket came in with (its cookies, and $request->principal as the app's middlewares left it), and a way to send
the browser to another page. After a reconnect, $request is the new connection's. $page is the page the
component is on, for a link back to it.

## Properties

### $page

```php
public private(set) string $page = '/';
```

The page's path and query as the browser's address shows it ("/films?tag[]=action"), for "sign in and come
back" links: `'/login?next=' . rawurlencode($this->connection->page)`. During the page's first render it is
the page request's; once connected the browser reports it, and again whenever #[Input(query: ...)]
properties change the address. Always a path on this site; "/" until the browser said where it is.

### $request

```php
public private(set) \Io\Http\Request $request;
```

**Type** `Request`

The request the WebSocket came in with, or the page's during its first render

## Methods

### navigate()

```php
public function navigate(string $url): void
```

After this action, the browser loads $url: a path on this site ("/admin"). It counts even if the action
throws afterwards, so a check can send a logged-out user to the login and stop the action. Not during the
page's first render, only from actions and timers.

**Parameters**

- `$url` `string`
