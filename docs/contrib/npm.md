<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit contrib/npm.php -->
<!-- Then run bin/docgen -->

# Npm Recipe

```php
require 'contrib/npm.php';
```

[Source](/contrib/npm.php)



## Configuration
- `bin/npm` *(optional)*: set npm binary, automatically detected otherwise.
## Usage
```php
after('deploy:update_code', 'npm:install');
```


## Configuration
### bin/npm
[Source](https://github.com/deployphp/deployer/blob/master/contrib/npm.php#L17)

## Configuration
- `bin/npm` *(optional)*: set npm binary, automatically detected otherwise.
## Usage
```php
after('deploy:update_code', 'npm:install');
```

```php title="Default value"
return which('npm');
```



## Tasks

### npm\:install {#npm-install}
[Source](https://github.com/deployphp/deployer/blob/master/contrib/npm.php#L27)

Installs npm packages.

Uses `npm ci` command. This command is similar to npm install,
except it's meant to be used in automated environments such as
test platforms, continuous integration, and deployment -- or
any situation where you want to make sure you're doing a clean
install of your dependencies.


