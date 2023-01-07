
# history

View history of all previously installed migrations.  Run with no options to get list of all transactions.

**Usage**

> `apex-migrations history [--package <PACKAGE>] [--txid <TXID>] [--limit X] [--start X] [--sort (asc|desc)]`

**Options**

Name | Required | Description
------------- |------------- |------------- 
--package | No | The package to list history for.
--txid | No | The transaction id# to list history of.  Get the id numbers by calling this command without any options.
--limit | No | The number of results to display.  Only applicable if run with no options, or with the --package option.
--start | No | Where in the result set to start.  Only applicable if run with no options, or with the --package option.
--sort | No | Order to sort results, can be either `desc` (default) or `asc`.  Only applicable if run with no options, or with the --package option.



