# Basics

Deployer is built around two concepts: [**hosts**](hosts.md) and [**tasks**](tasks.md). A **recipe** is a file
that defines both.

The CLI takes two arguments — a task and a [selector](selector.md):

```sh
$ dep deploy deployer.org
      ------ ------------
       task    selector
```

### Host Selection

- No selector — Deployer asks you to pick a host.
- One host in the recipe — selected automatically.
- `all` — every host.

By default, `dep` loads `deploy.php` or `deploy.maml` from the current directory. Pass `-f` / `--file` to point it
elsewhere:

```sh
$ dep --file=deploy.php deploy deployer.org
```

---

## Writing Your First Recipe

A minimal recipe:

```php
namespace Deployer;

host('deployer.org');

task('my_task', function () {
    run('whoami');
});
```

Run it:

```sh
$ dep my_task
task my_task
```

### Increasing Verbosity

`dep` only shows task names by default. Use `-v` to see commands and their output:

```sh
$ dep my_task -v
task my_task
[deployer.org] run whoami
[deployer.org] deployer
```

---

## Working with Multiple Hosts

Define more than one host:

```php
host('deployer.org');
host('medv.io');
```

Deployer reads `~/.ssh/config` like the `ssh` command. You can also set [connection options](hosts.md) in the
recipe.

Run a task on every host:

```sh
$ dep my_task -v all
task my_task
[deployer.org] run whoami
[medv.io] run whoami
[deployer.org] deployer
[medv.io] anton
```

### Controlling Parallelism

Tasks run in parallel on all selected hosts by default, which can interleave output. Use `--limit 1` to run one
host at a time:

```sh
$ dep my_task -v all --limit 1
task my_task
[deployer.org] run whoami
[deployer.org] deployer
[medv.io] run whoami
[medv.io] deployer
```

Per-task limits are also available — see [limit](tasks.md#limit).

---

## Configuring Hosts

Each host carries key-value config:

```php
host('deployer.org')->set('my_config', 'foo');
host('medv.io')->set('my_config', 'bar');
```

Read it inside a task with [currentHost](api.md#currenthost):

```php
task('my_task', function () {
    $myConfig = currentHost()->get('my_config');
    writeln("my_config: " . $myConfig);
});
```

Or shorter, with [get](api.md#get):

```php
task('my_task', function () {
    $myConfig = get('my_config');
    writeln("my_config: " . $myConfig);
});
```

Or inline with `{{...}}`:

```php
task('my_task', function () {
    writeln("my_config: {{my_config}}");
});
```

Escape with a backslash to emit a literal `{{`:

```php
run('echo \{{not_replaced}}'); // outputs: {{not_replaced}}
```

---

## Global Configurations

Hosts inherit global config:

```php
set('my_config', 'global');

host('deployer.org');
host('medv.io');
```

Both hosts now see `my_config = "global"`. Hosts can override:

```php
set('my_config', 'global');

host('deployer.org');
host('medv.io')->set('my_config', 'bar');
```

---

## Dynamic Configurations

A callback value is evaluated on first access and cached:

```php
set('whoami', function () {
    return run('whoami');
});

task('my_task', function () {
    writeln('Who am I? {{whoami}}');
});
```

When executed:

```sh
$ dep my_task all
task my_task
[deployer.org] Who am I? deployer
[medv.io] Who am I? anton
```

---

The cache is per-host: the callback runs once per host and the result sticks for the rest of the task tree.

```php
set('current_date', function () {
    return run('date');
});

task('my_task', function () {
    writeln('What time is it? {{current_date}}');
    run('sleep 5');
    writeln('What time is it? {{current_date}}');
});
```

```sh
$ dep my_task deployer.org -v
task my_task
[deployer.org] run date
[deployer.org] Wed 03 Nov 2021 01:16:53 PM UTC
[deployer.org] What time is it? Wed 03 Nov 2021 01:16:53 PM UTC
[deployer.org] run sleep 5
[deployer.org] What time is it? Wed 03 Nov 2021 01:16:53 PM UTC
```

---

## Overriding Configurations via CLI

Override any config value with `-o`:

```sh
$ dep my_task deployer.org -v -o current_date="I don't know"
task my_task
[deployer.org] What time is it? I don't know
[deployer.org] run sleep 5
[deployer.org] What time is it? I don't know
```

The callback never runs because `current_date` is already set.

:::note
To derive a value from a CLI-overridable config, use a callback. Plain `get()` at recipe load time captures the
default and cannot see `-o` overrides.

```php
set('dir_name', 'test');

// Evaluated at recipe load — captures the default, ignores -o overrides.
set('uses_original_dir_name', '/path/to/' . get('dir_name'));

// Evaluated lazily — sees the overridden value.
set('uses_overridden_dir_name', function () {
    return '/path/to/' . get('dir_name');
});

task('my_task', function () {
    writeln('Path: {{uses_original_dir_name}}');
    writeln('Path: {{uses_overridden_dir_name}}');
});
```

```sh
$ dep my_task deployer.org -v -o dir_name="prod"
task my_task
[deployer.org] Path: /path/to/test
[deployer.org] Path: /path/to/prod
```
:::
