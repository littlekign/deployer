<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit recipe/provision/databases.php -->
<!-- Then run bin/docgen -->

# Databases Recipe

```php
require 'recipe/provision/databases.php';
```

[Source](/recipe/provision/databases.php)


## Configuration
### db_type
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision/databases.php#L5)


:::info Autogenerated
The value of this configuration is autogenerated on access.
:::




### db_name
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision/databases.php#L15)



```php title="Default value"
return ask(' DB name: ', 'prod');
```


### db_user
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision/databases.php#L19)



```php title="Default value"
return ask(' DB user: ', 'deployer');
```


### db_password
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision/databases.php#L23)



```php title="Default value"
return askHiddenResponse(' DB password: ');
```



## Tasks

### provision\:databases {#provision-databases}
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision/databases.php#L28)

Provision databases.




### provision\:mysql {#provision-mysql}
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision/databases.php#L40)

Provision MySQL.




### provision\:mariadb {#provision-mariadb}
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision/databases.php#L51)

Provision MariaDB.




### provision\:postgresql {#provision-postgresql}
[Source](https://github.com/deployphp/deployer/blob/master/recipe/provision/databases.php#L62)

Provision PostgreSQL.




