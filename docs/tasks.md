# Tasks

Use [task()](api.md#task) to define a task. Add a description with [desc()](api.md#desc) before the task:

```php
desc('My task');
task('my_task', function () {
    // ...
});
```

Pass only the name to fetch an existing task and chain config on it:

```php
task('my_task')->disable();
```

## Task config

These methods are chained off `task('name')` to configure behavior.

### desc()

Set the task's description (shown by `dep list`).

```php
task('deploy', function () {
    // ...
})->desc('Task description');
```

Equivalent to the [desc()](api.md#desc) helper:

```php
desc('Task description');
task('deploy', function () {
    // ...
});
```

### once()

Run the task on a single host instead of all selected hosts.

### oncePerNode()

Run the task once per **node**, where a node is identified by [hostname](hosts.md#hostname). Useful when several
host aliases point at the same physical machine.

```php
host('foo')->setHostname('example.com');
host('bar')->setHostname('example.com');
host('pro')->setHostname('another.com');

task('apt:update', function () {
    // Runs twice: once for foo/bar (same hostname), once for pro.
    run('apt-get update');
})->oncePerNode();
```

### hidden()

Hide the task from `dep list`.

### addBefore()

Attach a before-hook. Same as [before()](api.md#before) but chained off the task.

### addAfter()

Attach an after-hook. Same as [after()](api.md#after) but chained off the task.

### limit()

Cap the number of hosts the task runs on concurrently. Defaults to unlimited.

### select()

Restrict the task to hosts matching a [selector](selector.md). Replaces any previous selector on the task.

### addSelector()

Add another selector clause (OR). The task runs on hosts that match `select()` or any added selector.

### verbose()

Always run the task as if `-v` were passed.

### disable()

Disable the task. Disabled tasks do not run, even when invoked.

### enable()

Re-enable a task that was disabled.

## Task grouping

Pass an array of task names to define a group task that runs them in order:

```php
task('deploy', [
    'deploy:prepare',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:symlink',
    'cleanup',
]);
```

## Task hooks

Run a task before or after another:

```php
task('deploy:done', function () {
    writeln('Deploy done!');
});

after('deploy', 'deploy:done');
```

`deploy:done` runs after `deploy` finishes.

:::note
Inspect attached hooks with:

```
dep tree deploy
```

:::
