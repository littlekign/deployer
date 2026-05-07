# Selector

A **selector** picks hosts to run a task on. Each host carries key-value **labels** (e.g. `stage: production`,
`role: web`); selectors match on those labels.

Define labels on hosts:

```php
host('web.example.com')
    ->setLabels([
        'type' => 'web',
        'env' => 'prod',
    ]);

host('db.example.com')
    ->setLabels([
        'type' => 'db',
        'env' => 'prod',
    ]);
```

Use `->addLabels()` to extend labels on an existing host.

Define a task that prints the labels:

```php
task('info', function () {
    writeln('type:' . get('labels')['type'] . ' env:' . get('labels')['env']);
});
```

Run it with a selector:

```bash
$ dep info env=prod
task info
[web.example.com] type:web env:prod
[db.example.com] type:db env:prod
```

Both hosts match `env=prod`. Restrict further:

```bash
$ dep info type=web
task info
[web.example.com] type:web env:prod
```

## Selector syntax

A selector is a list of conditions joined by `,` (OR) or `&` (AND).

**OR**: `type=web,env=prod` matches `type=web` *or* `env=prod`:

```bash
$ dep info 'type=web,env=prod'
task info
[web.example.com] type:web env:prod
[db.example.com] type:db env:prod
```

**AND**: `type=web & env=prod` matches both:

```bash
$ dep info 'type=web & env=prod'
task info
[web.example.com] type:web env:prod
```

**OR within a value**: `type=web|db & env=prod` matches `(type=web OR type=db) AND env=prod`:

```bash
$ dep info 'type=web|db & env=prod'
task info
[web.example.com] type:web env:prod
[db.example.com] type:db env:prod
```

**Negation**: `type!=web` excludes hosts labeled `type=web`:

```bash
$ dep info 'type!=web'
task info
[db.example.com] type:db env:prod
```

:::note
Multiple selector arguments are equivalent to a comma-joined list:
`dep info type=web env=prod` ≡ `dep info 'type=web,env=prod'`.

Bash autocompletion helps with selectors — see [installation](installation.md).
:::

### Special selectors

- `all` — every host.
- `alias=...` — match by host alias.

A token without `=` is treated as an alias, so `dep info web.example.com` ≡ `dep info alias=web.example.com`:

```bash
$ dep info web.example.com
task info
[web.example.com] type:web env:prod
```

```bash
$ dep info 'web.example.com' 'db.example.com'
$ # Same as:
$ dep info 'alias=web.example.com,alias=db.example.com'
```

## Using selectors from PHP

[select()](api.md#select) returns matching hosts:

```php
task('info', function () {
    $hosts = select('type=web|db,env=prod');
    foreach ($hosts as $host) {
        writeln('type:' . $host->get('labels')['type'] . ' env:' . $host->get('labels')['env']);
    }
});
```

[on()](api.md#on) runs a callback on each matched host:

```php
task('info', function () {
    on(select('all'), function () {
        writeln('type:' . get('labels')['type'] . ' env:' . get('labels')['env']);
    });
});
```

## Task selectors

Restrict a task to a fixed selector with [select()](tasks.md#select):

```php
task('info', function () {
    // ...
})->select('type=web|db,env=prod');
```

## Labels in YAML

YAML recipes support labels too:

```yaml
hosts:
  web.example.com:
    remote_user: deployer
    env:
      environment: production
    labels:
      env: prod
```

Don't confuse `env` (a config key) with `labels.env` (a label). They are independent:

```php
task('info', function () {
    writeln('env:' . get('env')['environment'] . ' labels.env:' . get('labels')['env']);
});
```

```bash
$ dep info env=prod
task info
[web.example.com] env:production labels.env:prod
```
