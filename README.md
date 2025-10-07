
# multiflexi-database

provide database configuration and migration for MultiFlexi's [executor](https://github.com/VitexSoftware/multiflexi-executor), [scheduler](https://github.com/VitexSoftware/multiflexi-scheduler), [api](https://github.com/VitexSoftware/multiflexi-server) and [web](https://github.com/VitexSoftware/MultiFlexi).

![MultiFlexi Chan](chan.jpeg?raw=true)

## Installation

Install prerequisites:

```sh
sudo apt install lsb-release wget apt-transport-https bzip2
```

Add the VitexSoftware repository and key:

```sh
wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg]  https://repo.vitexsoftware.com  $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo apt update
```

Install the package for your database (replace `DBTYPE` with `mysql`, `pgsql`, or `sqlite`):

### Skipping automatic migration

If you want to skip running database migrations during package installation or upgrade, set the environment variable `MULTIFLEXI_NOMIGRATE` before installing or upgrading the package. This is especially useful when building Docker images, so that migrations are not run during image creation.

```sh
sudo MULTIFLEXI_NOMIGRATE=1 apt install multiflexi-sqlite
```

When this variable is set, the `multiflexi-migrator` step in the post-installation scripts will be skipped.

```sh
sudo apt install multiflexi-DBTYPE
```

Supported database engines:

- MySQL
- SQLite
- PostgreSQL
- MariaDB
- MSSQL (experimental)

## MultiFlexi

multiflexi-database is part of [MultiFlexi](https://multiflexi.eu) suite.
See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/)
