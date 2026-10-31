---
title: "Broadcast"
description: "Renders every connected tab again: for state that tabs share through an app-wide service, such as a chat."
order: 100
generated: true
---

`Io\Live\Broadcast` · final class · namespace [`Io\Live`](index.md)

```php
final class Broadcast
```

Renders every connected tab again: for state that tabs share through an app-wide service, such as a chat. A
tab only renders after its own actions and timers; `$broadcast->changed()` after a change reaches the others.
Inject it into a component or a service, or take it with `$app->get(Broadcast::class)`.

With topics, `$broadcast->changed("comments:$anime")` renders only the components whose #[Topics] method names
one of them, and leaves every other tab alone.

Under a Tenma with Messaging\Channel::publish(), the other workers render their tabs too, and with `tenma --pubsub` every
process on that Redis.

## Methods

### changed()

```php
public function changed(string ...$topics): void
```

Every connected tab renders, and sends the components whose HTML changed. With $topics only the top-level
components that hold a component whose #[Topics] method names one of them.

**Parameters**

- `...$topics` `string`

### changedHere()

```php
public function changedHere(string ...$topics): void
```

Only this worker's tabs render: in a Messaging\Channel::subscribe() callback that has just brought this worker's state
up to date. Every worker runs it, so changed() would render every tab once per worker. $topics as in changed().

**Parameters**

- `...$topics` `string`
