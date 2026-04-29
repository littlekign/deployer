<?php
/*
## Configuration

- **bin/yarn** *(optional)*: set Yarn binary, automatically detected otherwise.

## Usage

```php
after('deploy:update_code', 'yarn:install');
after('yarn:install', 'yarn:build');
```
 */

namespace Deployer;

set('bin/yarn', function () {
    return which('yarn');
});

desc('Installs Yarn packages');
task('yarn:install', function () {
    run('cd {{release_path}} && {{bin/yarn}}');
});

desc('Runs Yarn build');
task('yarn:build', function () {
    run('cd {{release_path}} && {{bin/yarn}} build');
});
