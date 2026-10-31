---
title: "LiveOptions"
description: "Live's options, set with `$app->configure(fn (LiveOptions $live) => $live->editor = '...')`."
order: 100
generated: true
---

`Io\Live\LiveOptions` · class · namespace [`Io\Live`](index.md)

```php
class LiveOptions
```

Live's options, set with `$app->configure(fn (LiveOptions $live) => $live->editor = '...')`.

## Properties

### $editor

```php
public string $editor = 'vscode://file/{file}:{line}';
```

In dev, where a file in an error opens: `{file}` and `{line}` are filled in. VSCodium:
`vscodium://file/{file}:{line}`, PhpStorm: `phpstorm://open?file={file}&line={line}`.

### $maxComponents

```php
public int $maxComponents = 50;
```

How many top-level components one tab may mount. The browser asks for each, so this bounds what one
tab costs the worker; children placed by a template don't count.
