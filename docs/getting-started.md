# Getting Started

This tutorial walks you through provisioning a server with the [provision](recipe/provision.md) recipe and running
your first deploy.

## Step 1: Install Deployer {#install}

[Install Deployer](installation.md), then run this in your project directory:

```sh
dep init
```

Answer the prompts. Deployer creates a **deploy.php** or **deploy.yaml** recipe defining your hosts, tasks, and
imported recipes. Framework recipes extend the [common](recipe/common.md) recipe.

---

## Step 2: Provision a New Server {#provision}

:::note
Skip to [deployment](#deploy) if your server is already set up.
:::

### Setting Up Your VPS

Create an **Ubuntu** VPS on Linode, DigitalOcean, Vultr, AWS, GCP, or similar. The
[provision](recipe/provision.md) recipe targets Ubuntu.

Provisioning needs root SSH key auth, which recent Ubuntu images disable by default. Enable it now; you can
disable root SSH again once provisioning finishes.

:::tip
Point a DNS record at the server's IP so you can SSH by domain name.
:::

### Configuring `deploy.php`

Define a host with at least these two settings:

- **`remote_user`** — SSH username.
- **`deploy_path`** — where to deploy on the server.

```php
host('example.org')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '~/example');
```

If the server only has `root`, the `provision` recipe creates and configures a `deployer` user for you.

### Adding an Identity Key

Put your SSH key in `~/.ssh/config` instead of the recipe:

```
Host *
  IdentityFile ~/.ssh/id_rsa
```

### Provisioning the Server

Provision:

```sh
dep provision
```

:::tip

- Connect as a non-root user: `dep provision -o provision_user=your-user`
- Use `sudo` to become root: `dep provision -o become=root`

:::

Provisioning prompts for PHP version, database, and more. It takes about **5 minutes** and installs everything
needed to serve a website at [deploy_path](recipe/common.md#deploy_path).

---

## Step 3: Deploy Your Project {#deploy}

Deploy:

```sh
dep deploy
```

On failure, Deployer prints the error and the command that failed. Common cause: missing `.env` or credentials.
SSH into the server to edit files in place:

```sh
dep ssh
```

Resume from a specific step:

```sh
dep deploy --start-from deploy:migrate
```

---

## Step 4: Post-Deployment Configuration

After the first deploy, the server layout looks like this:

```
~/example                      // deploy_path
 |- current -> releases/1      // Symlink to current release
 |- releases                   // Directory for all releases
    |- 1                       // Latest release
       |- ...
       |- .env -> shared/.env  // Symlink to shared .env file
 |- shared                     // Shared files between releases
    |- ...
    |- .env                    // Shared .env file
 |- .dep                       // Deployer configuration files
```

### Web Server Setup

Point your web server at the `current` directory. Example for Nginx:

```nginx
root /home/deployer/example/current/public;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

The [provision recipe](recipe/provision.md) configures Caddy automatically, serving from
[public_path](recipe/provision/website.md#public_path).

---

## Step 5: Adding a Build Step

Add a build task to **deploy.php** and hook it after code updates:

```php
task('build', function () {
    cd('{{release_path}}');
    run('npm install');
    run('npm run prod');
});

after('deploy:update_code', 'build');
```

---

## Examining Deployments

List deployments with:

```sh
dep releases
```

Example output:

```
+---------------------+--------- deployer.org -------+--------+-----------+
| Date (UTC)          | Release     | Author         | Target | Commit    |
+---------------------+-------------+----------------+--------+-----------+
| 2021-11-05 14:00:22 | 1 (current) | Anton Medvedev | HEAD   | 943ded2be |
+---------------------+-------------+----------------+--------+-----------+
```

:::tip
During development, [dep push](recipe/deploy/push.md) ships a patch of local changes to the host without going
through git.
:::
