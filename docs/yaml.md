# YAML

YAML recipes are validated against
[schema.json](https://github.com/deployphp/deployer/blob/master/src/schema.json).

Example recipe:

```yaml
import:
  - recipe/laravel.php

config:
  repository: "git@github.com:example/example.com.git"
  remote_user: deployer

hosts:
  example.com:
    deploy_path: "~/example"

tasks:
  build:
    - cd: "{{release_path}}"
    - run: "npm run build"

after:
  deploy:failed: deploy:unlock
```

YAML and PHP recipes can import each other — write tasks needing closures in PHP and import them from YAML, or
the other way around with [import()](api.md#import).

For a richer alternative with comments, multiline strings, and trailing commas, see [MAML](maml.md).
