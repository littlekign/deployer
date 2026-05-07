# MAML Recipes

Deployer supports recipes written in [MAML](https://maml.dev), a minimal,
human-readable, machine-parsable configuration format. MAML extends JSON with
comments, multiline raw strings, optional commas, unquoted keys, and ordered
objects, while remaining strict about types and structure. Files use the
`.maml` extension.

The schema for a MAML recipe is declared in PHP at
[`MamlRecipe::schema()`](https://github.com/deployphp/deployer/blob/master/src/Import/MamlRecipe.php)
and validated on load. Validation errors point at the offending span with a
source snippet.

## Quick example

```maml
{
  # Import other recipes (php, maml, or yaml).
  import: [
    "recipe/common.php"
  ]

  config: {
    repository: "git@github.com:example/example.com.git"
  }

  hosts: {
    "example.com": {
      remote_user: "deployer"
      deploy_path: "~/example"
    }
  }

  tasks: {
    # Build the project
    build: [
      { cd: "{{release_path}}" }
      { run: "npm ci" }
      { run: "npm run build" }
    ]
  }

  after: {
    "deploy:failed": "deploy:unlock"
  }
}
```

Generate a starter recipe interactively with:

```bash
dep init
```

and choose `maml` when prompted for the recipe language.

## MAML syntax in 60 seconds

A MAML document is a single value, normally a top-level object `{ ... }`.

- **Comments**: `#` to end of line.
- **Strings**: double-quoted (`"..."`), with the usual escapes
  (`\t`, `\n`, `\r`, `\"`, `\\`, `\u{XXXX}`).
- **Raw strings**: triple-quoted (`"""..."""`), no escapes, newlines and
  whitespace preserved verbatim. Useful for embedding scripts.
- **Numbers**: integers (`5`, `-3`) and floats (`1.5`, `1e9`).
- **Booleans / null**: `true`, `false`, `null` (lowercase only).
- **Arrays**: `[ ... ]`, comma- *or* newline-separated.
- **Objects**: `{ key: value }`, comma- or newline-separated. Keys may be
  unquoted identifiers (letters, digits, `_`, `-`) or quoted strings. Hosts
  with dots (`"example.com"`) and hook names (`"deploy:failed"`) must be
  quoted.

Trailing commas are allowed everywhere. Duplicate keys inside an object are
not.

## Top-level sections

A recipe is an object with these optional keys, validated by the schema:

| Key | Description |
|---|---|
| `import` | String or array of strings. Paths to other recipes (`.php`, `.maml`, `.yaml`). |
| `config` | Object. Becomes calls to [`set()`](api.md#set). |
| `hosts` | Object. Each entry becomes [`host()`](api.md#host) (or [`localhost()`](api.md#localhost) when `local: true`). |
| `tasks` | Object. Each entry becomes a [`task()`](api.md#task). |
| `before` | Object mapping `task → hook(s)`. Becomes [`before()`](api.md#before). |
| `after` | Object mapping `task → hook(s)`. Becomes [`after()`](api.md#after). |
| `fail` | Object mapping `task → fallback task`. Becomes [`fail()`](api.md#fail). |

Any other top-level key is rejected with a schema error.

## `import`

Pull in other recipes. PHP recipes run as plain `require`, MAML and YAML
recipes are parsed and applied. This is how a MAML recipe gains access to
custom PHP tasks, callbacks, and helpers it cannot express directly.

```maml
{
  import: "recipe/laravel.php"
}
```

```maml
{
  import: [
    "recipe/common.php"
    "deploy/custom.php"
    "deploy/extras.maml"
  ]
}
```

## `config`

A flat object. Each key is forwarded to `set($key, $value)`. Values may be
strings, numbers, booleans, arrays, or nested objects, anything MAML can
express.

```maml
{
  config: {
    repository: "git@github.com:example/example.com.git"
    keep_releases: 5
    ssh_multiplexing: true
    shared_dirs: ["storage", "bootstrap/cache"]
  }
}
```

`config` does not accept PHP closures. To set values that need runtime
evaluation, import a `.php` recipe and call `set()` from there.

## `hosts`

Each entry creates a host. Keys with dots (`example.com`) must be quoted.
Inside, every key/value is forwarded to `Host::set()`, so all standard host
options are available (`remote_user`, `deploy_path`, `port`, `identity_file`,
`labels`, `ssh_arguments`, etc.).

```maml
{
  hosts: {
    "prod.example.com": {
      remote_user: "deployer"
      deploy_path: "/var/www/prod"
      labels: { stage: "production" }
    }
    "staging.example.com": {
      remote_user: "deployer"
      deploy_path: "/var/www/staging"
      labels: { stage: "staging" }
    }
  }
}
```

### Labels

Labels are key-value tags used by [selectors](selector.md). Define them as a nested object under `labels`:

```maml
{
  hosts: {
    "web.example.com": {
      remote_user: "deployer"
      labels: {
        type: "web"
        env: "prod"
      }
    }
    "db.example.com": {
      remote_user: "deployer"
      labels: {
        type: "db"
        env: "prod"
      }
    }
  }
}
```

Run a task on every `prod` host:

```bash
$ dep deploy env=prod
```

`labels.<key>` and a top-level config key with the same name (e.g. `env`) are independent — the selector only
looks at `labels`.

### Localhost

Set `local: true` to register the entry as a localhost via `localhost()`:

```maml
{
  hosts: {
    "dev": {
      local: true
      deploy_path: "/tmp/dev"
    }
  }
}
```

## `tasks`

A task entry is one of:

1. **Group task**: an array of strings. Runs the listed tasks in order.
2. **Step task**: an array of step objects. Each step is a single action.

### Group tasks

```maml
{
  tasks: {
    deploy: [
      "deploy:prepare"
      "deploy:vendors"
      "deploy:publish"
    ]
  }
}
```

### Step tasks

Each step is an object with exactly one action key (`cd`, `run`,
`runLocally`, `upload`, `download`) or one or more task-config keys (`desc`,
`once`, `hidden`, `limit`, `select`). Steps are executed in declaration
order. Task-config steps modify the task itself and do not interrupt the
chain of actions.

```maml
{
  tasks: {
    build: [
      { desc: "Build assets" }
      { once: true }
      { cd: "{{release_path}}" }
      { run: "npm ci" }
      { run: "npm run build" }
    ]
  }
}
```

### Task description from comments

Leading `#` comments directly above a task key become the task's description
(joined with newlines). The `desc` step takes precedence if both are
present.

```maml
{
  tasks: {
    # Deploy the application
    # Runs migrations, builds assets, restarts services
    deploy: [
      { run: "echo deploying" }
    ]
  }
}
```

### Task config keys

Set these inside step objects to control task metadata:

| Key | Type | Effect |
|---|---|---|
| `desc` | string | Sets the description (shown in `dep list`). |
| `once` | bool | Run on a single host only. |
| `hidden` | bool | Hide from `dep list`. |
| `limit` | number | Maximum hosts to run on in parallel. |
| `select` | string | Host selector expression (see [Selector](selector.md)). |

```maml
{
  tasks: {
    migrate: [
      { desc: "Run database migrations" }
      { once: true }
      { limit: 1 }
      { select: "stage=production" }
      { run: "php artisan migrate --force" }
    ]
  }
}
```

## Step actions

### `cd`

Change the working directory for subsequent `run` steps in the same task.

```maml
{ cd: "{{release_path}}" }
```

### `run`

Execute a command on the remote host. Equivalent to
[`run()`](api.md#run). All optional keys mirror the PHP function:

```maml
{
  run: "php artisan migrate --force"
  cwd: "{{release_path}}"
  env: {
    APP_ENV: "production"
  }
  secrets: {
    DB_PASSWORD: "s3cret"
  }
  timeout: 600
  idleTimeout: 120
  nothrow: false
  forceOutput: true
}
```

| Option | Type | Default |
|---|---|---|
| `cwd` | string | host's `cwd`/`deploy_path` |
| `cd` | string | (alias of `cwd`) |
| `env` | map<string, string> | none |
| `secrets` | map<string, string> | none |
| `timeout` | number (seconds) | 300 |
| `idleTimeout` | number (seconds) | none |
| `nothrow` | bool | `false` |
| `forceOutput` | bool | `false` |

Use a raw string for multiline commands:

```maml
{
  run: """
    set -e
    php artisan down
    php artisan migrate --force
    php artisan up
  """
}
```

### `runLocally`

Run a command on the local machine. Mirrors
[`runLocally()`](api.md#runlocally).

```maml
{
  runLocally: "git rev-parse HEAD"
  cwd: "."
  shell: "/bin/bash"
  timeout: 60
}
```

Supports the same options as `run` plus `shell`, except `cd` (use `cwd`).

### `upload`

Transfer files to the remote host. Mirrors
[`upload()`](api.md#upload). `src` may be a single path or an array of
paths.

```maml
{
  upload: {
    src: "build/"
    dest: "{{release_path}}/public/"
  }
}
```

```maml
{
  upload: {
    src: ["dist/app.js", "dist/app.css"]
    dest: "{{release_path}}/public/assets/"
  }
}
```

### `download`

Transfer files from the remote host to the local machine. Mirrors
[`download()`](api.md#download).

```maml
{
  download: {
    src: "{{deploy_path}}/shared/.env"
    dest: ".env.production"
  }
}
```

## `before`, `after`, `fail`

Hooks attach tasks to other tasks. The value may be a single task name or an
array of task names. Quote names that contain `:`.

```maml
{
  before: {
    deploy: ["deploy:prepare", "build"]
  }

  after: {
    "deploy:failed": "deploy:unlock"
    deploy: "deploy:cleanup"
  }

  fail: {
    deploy: "deploy:rollback"
  }
}
```

For arrays, hooks attach in declaration order.

## Mixing MAML, PHP, and YAML

MAML covers the declarative parts of a recipe: config, hosts, tasks built
from standard steps, hooks. Anything that needs runtime PHP (closures, the
`set('var', fn () => ...)` pattern, custom step types, conditional logic)
belongs in a `.php` recipe imported from MAML, or vice-versa.

From a PHP recipe, import MAML using [`import()`](api.md#import):

```php
import('deploy.maml');
```

From a MAML recipe, list the PHP file under `import`:

```maml
{
  import: ["deploy/extras.php"]
}
```

The same applies to YAML, see [YAML](yaml.md).

## Validation errors

When a recipe does not match the schema, Deployer raises a
`SchemaException` with the offending span and a snippet of source. Common
causes:

- Unknown top-level key (anything outside the table above).
- A step object with multiple action keys (each step is one action).
- Wrong types, e.g. `config: "string"` instead of an object, or `tasks: [...]`
  instead of an object.
- Hook target not declared as a string or array of strings.

Fix the structure, re-run, and the error trace will pinpoint the line.

## Output and tooling

- `dep init` generates a starter `deploy.maml`.
- `dep config` prints config in MAML by default; use `--format=json` or
  `--format=yaml` for other formats.
- Editor support for MAML is available for VS Code, IntelliJ, Vim, and
  CodeMirror, see [maml.dev](https://maml.dev).
