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

`dep run <command> [selector]` runs a one-off shell command on the selected hosts:

```
$ dep run 'uptime -p' all
[prod01] up 3 weeks, 2 days, 4 hours
[prod02] up 1 week, 6 days
```

Useful flags:

- `-o key=value` — override config (same as deploy commands).
- `-t, --timeout=<sec>` — command timeout (default 300).
- `-r, --raw` — print stdout only, no `[host]` prefix.

## SSH into a host

`dep ssh [host]` opens an interactive SSH session using Deployer's host config (alias, port, identity file,
`remote_user`, etc.).

```
dep ssh                # asks which host
dep ssh deployer.org   # connects directly
```

After connecting, the working directory is `{{deploy_path}}` (or `{{current_path}}` if it exists).

## Inspect configuration

`dep config [selector]` prints resolved config for the selected hosts. Default format is MAML:

```
dep config                  # asks which host
dep config all              # every host
dep config --format=json
dep config --format=maml
```

Useful for debugging variable interpolation and seeing what callbacks resolve to.

## Rolling back

`dep rollback` re-points `current` at the most recent good release:

```
dep rollback
```

What it does:

1. Reads `releases/` and picks the most recent release before `current` that is not marked `BAD_RELEASE`.
2. Re-symlinks `current → releases/<candidate>`.
3. Writes a `BAD_RELEASE` file (with timestamp and user) into the previously-current release so it is skipped on
   future rollbacks.

Override the target release with `-o rollback_candidate=<release_id>`.

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
