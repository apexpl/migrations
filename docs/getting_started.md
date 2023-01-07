
# Getting Started

Once installed, you must define the database credentials within the container defitions file (defaults to ~/config/container.php).  Open this file, and modify the `DbInterface::class` item as necessary with your database credentials.  If desired, you're welcome to use either the PostgreSQL or SQLite drivers as well, and please see the [APex Database Layer](https://github.com/apexpl/db/blob/master/docs/connections.md) documentation for details.  

Please note, if you are including migration with a package that already loads the `DbInterface::class` item into the container, you may ignore this step altogether.


## YAML Config File

A small YAML file is required, which can be located anywhere you wish, and defaults to `/config/migrations.yml.  Tbe below table lists the root elements of this file:

Element | Type | Description
------------- |------------- |------------- 
table_name | string | The desired database table name that will hold migration data.  Defaults to "internal_migrations", and will be automatically created within your database.
author | array | 
packages | array | An array of all packages that are being monitored for migrations.  See below for details.

Each element of `packages` is an associative array, named the desired name of the package.  The associative array has two elements:

* `dir` - The directory on the server where migrations are stored.
* `namespace` - Any desired namespace that migration classes will use.  This does not need to be found by autoloader, and may be anything you wish.

Please note, the `packages` element should always contain a package named "default".  When a command requiring a package such as create is run with no package defined, it will default to this package.

An example YAML file is below:

~~~
table_name: myapp_migrations

author:
  username: jsmith
  full_name: John Smith
  email: jsmith@domain.com

packages:

  blog:
    dir: /var/www/modules/blog/migrations
    namespace: "Migrations\\Blog"

  ecommerce:
    dir: /var/www/modules/ecommerce/migrations
    namespace: "Migrations\\Ecommerce"

  default:
    dir: /var/www/app/migrations
    namespace: "Migrations\\App"
~~~


