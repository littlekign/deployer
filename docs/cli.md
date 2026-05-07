# CLI Usage

For project installs, alias `dep`:

```bash
alias dep='vendor/bin/dep'
```

Install shell completion for tasks, options, host names, and configs. On macOS:

```bash
brew install bash-completion
dep completion bash > /usr/local/etc/bash_completion.d/deployer
```

See [installation](installation.md#autocomplete-support) for zsh and fish.

## Overriding configuration options

Use `-o` to override any config value at the command line. Given this in `deploy.php`:

```php
set('ssh_multiplexing', false);
```

Re-enable [ssh multiplexing](https://en.wikibooks.org/wiki/OpenSSH/Cookbook/Multiplexing) for one run:

```
dep deploy -o ssh_multiplexing=true
```

Pass `-o` multiple times to override more than one value:

```
dep deploy -o ssh_multiplexing=true -o branch=master
```

## Running arbitrary commands

Run an ad-hoc command on the selected hosts:

```
dep run 'uptime -p'
```

## Tree command

`dep tree <task>` visualizes [task grouping](tasks.md#task-grouping) and [before/after hooks](tasks.md#addbefore):

```
$ dep tree deploy
The task-tree for deploy:
└── deploy
    ├── deploy:prepare
    │   ├── deploy:info
    │   ├── deploy:setup
    │   ├── deploy:lock
    │   ├── deploy:release
    │   ├── deploy:update_code
    │   ├── build  // after deploy:update_code
    │   ├── deploy:shared
    │   └── deploy:writable
    ├── deploy:vendors
    ├── artisan:storage:link
    ├── artisan:config:cache
    ├── artisan:route:cache
    ├── artisan:view:cache
    ├── artisan:migrate
    └── deploy:publish
        ├── deploy:symlink
        ├── deploy:unlock
        ├── deploy:cleanup
        └── deploy:success
```

## Execution plan

Deployer flattens the task tree and decides task order per host before running. `--plan` prints the table without
executing anything:

```
$ dep deploy --plan all
┌──────────────────────┬──────────────────────┬──────────────────────┬──────────────────────┐
│ prod01               │ prod02               │ prod03               │ prod04               │
├──────────────────────┼──────────────────────┼──────────────────────┼──────────────────────┤
│ deploy:info          │ deploy:info          │ deploy:info          │ deploy:info          │
│ deploy:setup         │ deploy:setup         │ deploy:setup         │ deploy:setup         │
│ deploy:lock          │ deploy:lock          │ deploy:lock          │ deploy:lock          │
│ deploy:release       │ deploy:release       │ deploy:release       │ deploy:release       │
│ deploy:update_code   │ deploy:update_code   │ deploy:update_code   │ deploy:update_code   │
│ build                │ build                │ build                │ build                │
│ deploy:shared        │ deploy:shared        │ deploy:shared        │ deploy:shared        │
│ deploy:writable      │ deploy:writable      │ deploy:writable      │ deploy:writable      │
│ deploy:vendors       │ deploy:vendors       │ deploy:vendors       │ deploy:vendors       │
│ artisan:storage:link │ artisan:storage:link │ artisan:storage:link │ artisan:storage:link │
│ artisan:config:cache │ artisan:config:cache │ artisan:config:cache │ artisan:config:cache │
│ artisan:route:cache  │ artisan:route:cache  │ artisan:route:cache  │ artisan:route:cache  │
│ artisan:view:cache   │ artisan:view:cache   │ artisan:view:cache   │ artisan:view:cache   │
│ artisan:migrate      │ artisan:migrate      │ artisan:migrate      │ artisan:migrate      │
│ deploy:symlink       │ -                    │ -                    │ -                    │
│ -                    │ deploy:symlink       │ -                    │ -                    │
│ -                    │ -                    │ deploy:symlink       │ -                    │
│ -                    │ -                    │ -                    │ deploy:symlink       │
│ deploy:unlock        │ deploy:unlock        │ deploy:unlock        │ deploy:unlock        │
│ deploy:cleanup       │ deploy:cleanup       │ deploy:cleanup       │ deploy:cleanup       │
│ deploy:success       │ deploy:success       │ deploy:success       │ deploy:success       │
└──────────────────────┴──────────────────────┴──────────────────────┴──────────────────────┘
```

The `deploy.php` for the table above:

```php
host('prod[01:04]');
task('deploy:symlink')->limit(1);
```

## The `runLocally` working dir

`runLocally()` runs relative to the recipe file's directory by default. Override globally with an environment
variable:

```
DEPLOYER_ROOT=. dep taskname
```

Or per call via the `cwd:` argument:

```php
runLocally('ls', cwd: '/root/directory');
```

## Play blackjack

> Yeah, well. I'm gonna go build my own theme park... with blackjack and hookers!
>
> In fact, forget the park!
>
> — Bender

```
dep blackjack
```
