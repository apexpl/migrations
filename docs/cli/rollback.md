
# rollback

Rollback previously installed migrations.

**Usage**

> `apex-migrations rollback [--txid <TXID>] [--package <PACKAGE>] [--last X] [--all]`

**Options**

Name | Required | Description
------------- |------------- |------------- 
--txid | No | The transaction id# to roll back up to and including.
--package | No | The package to rollback migrations on.
--last | No | if `--package` is defined, the last number of migrations installed on the package to rollback.  Otherwise, the number of transactions to rollback.
`all` | No | Does not require a value, and if present along with the `--package` option will remove all migrations installed on that package.


