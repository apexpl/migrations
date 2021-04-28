
# migrate

Install migrations.  If run with no options, will install all pending migrations available for all packages.

**Usage**

> `apex-migrations migrate [--package <PACKAGE>] [--name <NAME>]`

**Options**

Name | Required | Description
------------- |------------- |------------- 
--package | No | The package to install migrations for.
--name | No | The specific migration name to install.  Can be combined with `--package` to further specify which package the migration name exists in to avoid collision errors.



