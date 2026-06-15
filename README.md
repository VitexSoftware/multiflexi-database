
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

- **MySQL** / **MariaDB** (fully supported)
- **PostgreSQL** (fully supported)
- **SQLite** (fully supported)
- **SQL Server** (experimental support)

All MultiFlexi components are designed to work seamlessly with any of these database backends through database abstraction layers.

## Encryption keys

MultiFlexi stores credential values encrypted with AES-256-GCM/CBC.
Three keys are maintained in the `encryption_keys` table: `default`, `credentials`, and `personal_data`.

The `EncryptionKeysSeeder` initialises these keys during first install.
Each key is a freshly generated 256-bit random value that is itself encrypted
with `ENCRYPTION_MASTER_KEY` before being stored in the database.

### Requirement

`ENCRYPTION_MASTER_KEY` (or `MULTIFLEXI_MASTER_KEY`) must be set in the
environment **or** in `/etc/multiflexi/database.env` before the seeder is run.
If the variable is absent the seeder prints an error and exits without
modifying the database.

### Automatic execution

The Debian postinst scripts for `multiflexi-mysql`, `multiflexi-pgsql` and
`multiflexi-sqlite` call the seeder automatically after `multiflexi-migrator`:

```sh
phinx seed:run -c /usr/lib/multiflexi-database/phinx-adapter.php -s EncryptionKeysSeeder
```

### Manual execution

```sh
export ENCRYPTION_MASTER_KEY="your-very-secret-master-key"
phinx seed:run -c /usr/lib/multiflexi-database/phinx-adapter.php -s EncryptionKeysSeeder
```

### Idempotency

The seeder is safe to run repeatedly.
Rows whose `key_data` already contains a real (non-placeholder) value are
never touched.
Only rows that are missing entirely, or that still contain the
`PLACEHOLDER_KEY_TO_BE_REPLACED` sentinel inserted by the migration, are
written.

## MultiFlexi

multiflexi-database is part of [MultiFlexi](https://multiflexi.eu) suite.
See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/)
