
# Apex Migrations

A lightweight migrations package designed to promote SQL schemas written in SQL, support projects with multiple packages / repositories, and work across mySQL, PostgreSQL and SQLIte from one database schema.  It supports:

* Maintain sets of migrations amongst multiple packages / repositories.
* Groups migrations into transactions when they were installed, allowing easy rollbacks.
* Includes author information within each migration.
* Automatically coverts SQL statements between mySQL, PostgreSQL and SQLite allowing interopable database schemas.


## Installation

Install via Composer with:

> `composer require apex/migrations`


## Table of Contents

1. [Getting Started](https://github.com/apexpl/migrations/blob/master/docs/getting_started.md)
2. [Migration Class](https://github.com/apexpl/migrations/blob/master/docs/migration.md)
2. CLI Commands (./apex-migrations)
    1. [create](https://github.com/apexpl/migrations/blob/master/docs/cli/create.md)
    2. [status](https://github.com/apexpl/migrations/blob/master/docs/cli/status.md)
    3. [history](https://github.com/apexpl/migrations/blob/master/docs/cli/history.md)
    4. [migrate](https://github.com/apexpl/migrations/blob/master/docs/cli/migrate.md)
    5. [rollback](https://github.com/apexpl/migrations/blob/master/docs/cli/rollback.md)



## Basic Usage

~~~php
use Apex\Syrus\Syrus;

// Start
$syrus = new Syrus();

// Assign some variables
$syrus->assign('name', 'value');

// Assign array
$location = [
    'city' => 'Toronto', 
    'province' => 'Ontario', 
    'country' => 'Canada'
];
$syrus->assign('loc', $location);

// Add foreach blocks
$syrus->addBlock('users', ['username' => 'jsmith', 'email' => 'jsmith@domain.com']);
$syrus->addBlock('users', ['username' => 'mike', 'email' => 'mike@domain.com']);

// ADd error callout
$this->addCallout('Uh oh, there was a problem.', 'error');

// Render template
echo $syrus->render('contact.html');

// Or, use auto-routing and render template based on URI being viewed.
echo $syrus->render();
~~~


## Support

If you have any questions, issues or feedback for Syrus, please feel free to drop a note on the <a href="https://reddit.com/r/apexpl/">ApexPl Reddit sub</a> for a prompt and helpful response.


## Follow Apex

Loads of good things coming in the near future including new quality open source packages, more advanced articles / tutorials that go over down to earth useful topics, et al.  Stay informed by joining the <a href="https://apexpl.io/">mailing list</a> on our web site, or follow along on Twitter at <a href="https://twitter.com/mdizak1">@mdizak1</a>.



