# MAML Recipes

[MAML](https://maml.dev) is a JSON superset with comments, raw multiline strings, optional commas, unquoted keys,
and ordered objects. Files use the `.maml` extension.

Recipes are validated on load against
[`MamlRecipe::schema()`](https://github.com/deployphp/deployer/blob/master/src/Import/MamlRecipe.php).
Validation errors point at the offending span with a source snippet.

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

Generate a starter recipe with `dep init` and pick `maml` when prompted.

## MAML syntax in 60 seconds

A MAML document is a single value, normally a top-level object `{ ... }`.

- **Comments**: `#` to end of line.
- **Strings**: `"..."` with standard escapes (`\t`, `\n`, `\r`, `\"`, `\\`, `\u{XXXX}`).
- **Raw strings**: `"""..."""`, no escapes, newlines preserved. Use for embedded scripts.
- **Numbers**: integers (`5`, `-3`) and floats (`1.5`, `1e9`).
- **Booleans / null**: lowercase `true`, `false`, `null`.
- **Arrays**: `[ ... ]`, comma- or newline-separated.
- **Objects**: `{ key: value }`, comma- or newline-separated. Keys are unquoted identifiers (letters, digits, `_`,
  `-`) or quoted strings. Quote keys with dots (`"example.com"`) and colons (`"deploy:failed"`).

Trailing commas allowed. Duplicate keys are not.

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

Pull in other recipes. `.php` files are `require`d; `.maml` and `.yaml` files are parsed and applied. Use
imports to bring custom PHP tasks, callbacks, or helpers into a MAML recipe.

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

Built-in `recipe/*` and `contrib/*` paths resolve via PHP's include path — no need for `__DIR__` or absolute
paths. See [import()](api.md#import).

## `config`

Each key calls `set($key, $value)`. Values can be any MAML type — string, number, bool, array, or nested object.

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

`config` does not accept PHP closures. For runtime-evaluated values, import a `.php` recipe and `set()` from
there.

## `hosts`

Each entry calls `host()`. Quote keys with dots. Every nested key/value is forwarded to `Host::set()`, so all
standard host options work: `remote_user`, `deploy_path`, `port`, `identity_file`, `labels`, `ssh_arguments`, etc.

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

A task entry is either:

1. **Group task** — array of strings. Runs the listed tasks in order.
2. **Step task** — array of step objects. Each step is a single action or one task-config key.

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

Each step is an object with **exactly one** action key (`cd`, `run`, `runLocally`, `upload`, `download`) or one
task-config key (`desc`, `once`, `hidden`, `limit`, `select`). Steps run in declaration order. Config-only steps
adjust task metadata and do not break the action chain.

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

`#` comments directly above a task key become its description (joined with newlines). A `desc` step takes
precedence if both are present.

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

Use these step keys to control task metadata. They mirror the chained methods in [Tasks](tasks.md).

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

Each action mirrors the PHP function it is named after.

### `cd`

Change the working directory for subsequent `run` steps in the same task. See [cd()](api.md#cd).

```maml
{ cd: "{{release_path}}" }
```

### `run`

Run a command on the remote host. See [run()](api.md#run).

```maml
{
  run: "php artisan migrate --force"
  cwd: "{{release_path}}"
  env:     { APP_ENV: "production" }
  secrets: { DB_PASSWORD: "s3cret" }
  timeout: 600
  idleTimeout: 120
  nothrow: false
  forceOutput: true
}
```

| Key | Type | Default |
|---|---|---|
| `cwd` | string | `{{working_path}}` |
| `cd` | string | alias of `cwd` |
| `env` | object | none |
| `secrets` | object | none |
| `timeout` | seconds | 300 |
| `idleTimeout` | seconds | none |
| `nothrow` | bool | `false` |
| `forceOutput` | bool | `false` |

Multiline commands work nicely with raw strings:

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

Run a command on the local machine. See [runLocally()](api.md#runlocally). Same options as `run` plus `shell`,
minus `cd` (use `cwd`).

```maml
{
  runLocally: "git rev-parse HEAD"
  cwd: "."
  shell: "/bin/bash"
  timeout: 60
}
```

### `upload`

Send files to the host. See [upload()](api.md#upload). `src` may be a string or array.

```maml
{
  upload: {
    src: "build/"
    dest: "{{release_path}}/public/"
  }
}

{
  upload: {
    src: ["dist/app.js", "dist/app.css"]
    dest: "{{release_path}}/public/assets/"
  }
}
```

### `download`

Pull files from the host. See [download()](api.md#download).

```maml
{
  download: {
    src: "{{deploy_path}}/shared/.env"
    dest: ".env.production"
  }
}
```

## `before`, `after`, `fail`

Attach hooks to tasks. The value is a task name or an array of names. Quote names with `:`.

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

Arrays attach in declaration order.

## Mixing MAML, PHP, and YAML

MAML covers declarative parts: config, hosts, step tasks, hooks. Anything that needs runtime PHP — closures,
`set('var', fn () => ...)`, custom step types, conditional logic — belongs in a `.php` recipe and gets imported
both ways.

From PHP, import a MAML recipe:

```php
import('deploy.maml');
```

From MAML, list the PHP file under `import`:

```maml
{
  import: ["deploy/extras.php"]
}
```

YAML works the same — see [YAML](yaml.md).

## Validation errors

A recipe that violates the schema raises a `SchemaException` pointing at the offending span. Common causes:

- Unknown top-level key (only the keys in the table above are valid).
- A step object with more than one action key.
- Wrong type — e.g. `config: "string"` instead of an object, or `tasks: [...]` instead of an object.
- Hook target that is not a string or array of strings.

## Tooling

- Editor support for VS Code, IntelliJ, Vim, and CodeMirror is listed at [maml.dev](https://maml.dev).
