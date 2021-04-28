
# Migration

Once configured as outlined in the [Getting Started](getting_started.md) page, you may create a new migration with the command:

> `./vendor/bin/apex-migrations create`

Every migration has its own directory, which consts of three files:

* `migrate.php` - The PHP classed executed during install / rollback of migration.
* `install.sql` - Blank SQL file executed during migration install.
* `rollback.sql` - Blank SQL file executed during migration rollback.

The `migrate.php` file supports six different methods, as described in the below table:

Method | Required | Description
------------- |------------- |------------- 
`preInstall()` | No | If exists, will be executed before the `install()` method.
`install()` | Yes | Always executed when the migration is installed, and should perform all necessary SQL statements against the database.
`postInstall()` | No | If exists, will be executed adter the `install()` method has finished.
`preRollback()` | No | If exists, will be executed before the `rollback()` method.
`rollback()` | Yes | Always executed when the migration is removed, and should perform all necessary SQL statements to remove the migration.
`postRollback()` | No | If exists, will be executed after the `rollback()` method has finished.


## Performing Migrations

You may check the status of your database anytime with the command:

> `./vendor/bin/apex-migrations status`

You may install any pending migrations anytime with the command:

> `./vendor/bin/apex-migrations migrate`








