### Migration Compatibility Rule

All migrations must support MySQL, PostgreSQL, and SQLite. Ensure that the migration scripts are tested and compatible with all three database systems.

## Troubleshooting

### Foreign Key Constraint Error

If you encounter the following error:

```
PDOException: SQLSTATE[HY000]: General error: 1005 Can't create table `multiflexi`.`app_translations` (errno: 150 "Foreign key constraint is incorrectly formed") in /home/vitex/Projects/Multi/multiflexi-database/vendor/robmorgan/phinx/src/Phinx/Db/Adapter/PdoAdapter.php:197
```

The reason is that the key columns of the related tables are not of the same type (including unsigned flag) . Ensure that the data types and lengths of the columns in the foreign key relationship match exactly.

To prevent issues with foreign key constraints in MySQL, ensure that the columns used in foreign key relationships are of the same type and length, including the unsigned attribute. For example, if a column is defined as `INT(11) UNSIGNED` in one table, the corresponding column in the related table must also be defined as `INT(11) UNSIGNED`.

```php
// Check if the database is MySQL
$databaseType = $this->getAdapter()->getOption('adapter');
$unsigned = ($databaseType === 'mysql') ? ['signed' => false] : [];

// Create the example table
$runtemplates = $this->table('example');
$runtemplates
    ->addColumn('another_table_id', 'integer', array_merge(['null' => false], $unsigned))
```

After every single edit to a PHP file, always run `php -l` on the edited file to lint it and ensure code sanity before proceeding further. This is mandatory for all PHP code changes.
