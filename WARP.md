# WARP.md - Working AI Reference for MultiFlexi Database

## Project Overview
**Type**: Database Configuration & Migration Package
**Purpose**: Provides database configuration and migration for MultiFlexi components (executor, scheduler, API, and web)
**Status**: Active
**Repository**: https://github.com/VitexSoftware/multiflexi-database

## Key Technologies
- Database Migration Tools
- Support for Multiple Database Engines:
  - MySQL
  - PostgreSQL
  - SQLite
  - MariaDB
  - MSSQL (experimental)
- Debian Packaging

## Architecture & Structure
```
multiflexi-database/
├── migrations/          # Database migration files
├── debian/              # Debian packaging files
├── config/              # Database configuration templates
└── scripts/             # Migration and setup scripts
```

## Development Workflow

### Prerequisites
- Supported database engine (MySQL, PostgreSQL, SQLite, MariaDB, or MSSQL)
- Debian/Ubuntu system for package installation

### Setup Instructions
```bash
# Add VitexSoftware repository
sudo apt install lsb-release wget apt-transport-https bzip2
wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg] https://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo apt update

# Install for your database type (replace DBTYPE with mysql, pgsql, or sqlite)
sudo apt install multiflexi-DBTYPE
```

### Migration Control
```bash
# Skip automatic migration during installation (useful for Docker)
sudo MULTIFLEXI_NOMIGRATE=1 apt install multiflexi-sqlite

# Run migrations manually
multiflexi-migrator
```

## Key Concepts
- **Database Migrations**: Versioned schema changes for MultiFlexi database
- **Multi-Engine Support**: Single codebase supporting multiple database backends
- **Package Variants**: Separate Debian packages for each database type
- **Automatic Migration**: Migrations run during package install/upgrade by default

## Common Tasks

### Install for MySQL
```bash
sudo apt install multiflexi-mysql
```

### Install for PostgreSQL
```bash
sudo apt install multiflexi-pgsql
```

### Install for SQLite
```bash
sudo apt install multiflexi-sqlite
```

### Skip Migrations (for Docker images)
```bash
# Set environment variable before installation
export MULTIFLEXI_NOMIGRATE=1
sudo -E apt install multiflexi-mysql
```

## Integration Points
- **MultiFlexi Web**: Main web interface
- **MultiFlexi Executor**: Job execution engine
- **MultiFlexi Scheduler**: Job scheduling component
- **MultiFlexi API Server**: REST API service

## Deployment
- **Target Environment**: Debian/Ubuntu servers
- **Distribution**: Via VitexSoftware APT repository
- **Docker Support**: Use MULTIFLEXI_NOMIGRATE=1 for image builds

## Troubleshooting
- **Migration Failures**: Check database permissions and connectivity
- **Package Conflicts**: Ensure only one multiflexi-DBTYPE package is installed
- **Connection Issues**: Verify database credentials in configuration

## Related Projects
- MultiFlexi (main project)
- multiflexi-executor
- multiflexi-scheduler
- multiflexi-server
- multiflexi-cli

## Additional Notes
- Part of the MultiFlexi suite for business automation
- Migrations are idempotent and can be run multiple times safely
- Database choice impacts performance and scalability characteristics