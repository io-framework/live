---
title: "Components"
description: "The app's live components: those given to LivePlugin, and those another plugin adds in its boot(), which takes this service as a parameter: `boot(Components $components)`, then `$components->add(StampCard::class)`."
order: 100
generated: true
---

`Io\Live\Components` · final class · namespace [`Io\Live`](index.md)

```php
final class Components
```

The app's live components: those given to LivePlugin, and those another plugin adds in its boot(), which takes
this service as a parameter: `boot(Components $components)`, then `$components->add(StampCard::class)`. Only
these can be created; the browser names them by selector. The templates are compiled on first use, once every
selector is known. A component added later (one an app generates while it runs) is compiled at once; the
templates compiled before keep its tag as text, so a view or the app's code places it.

## Methods

### add()

```php
public function add(string ...$classes): \Io\Live\Components
```

Adds components: a class without #[Component], a selector that is taken or no custom element name, or a
missing template is refused at once. After the first page, each one's template is compiled right away and a
template that does not compile is refused too; the templates compiled before do not place its tag.

**Parameters**

- `...$classes` `class-string`: Classes with a #[Component] attribute

**Returns** [`Components`](Components.md)

**Throws**

- [`InvalidArgumentException`](https://www.php.net/manual/en/class.invalidargumentexception.php): for a class that cannot be a component, or after the first page a template that does not compile

### selectors()

```php
public function selectors(): array
```

**Returns** `list<string>`: The selectors of every component added so far
