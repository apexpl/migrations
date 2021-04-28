<?php
declare(strict_types = 1);

namespace Apex\Migrations\Cli;

use Apex\Migrations\Cli\Cli;

/**
 * Help screends
 */
class Help
{

    /**
     * Process
     */
    public function process():void
    {

        // Get options
        list($args, $opt) = Cli::getArgs(['alias', 'package','branch']);
        $cmd = $args[1] ?? '';

        // Check for method
        if (!method_exists(__CLASS__, $cmd)) { 
            Cli::send("No command exists at $cmd.  use 'apex-migrations help' for a list of available migrations.\r\n\r\n");
            Cli::showHelp();
            return;
        }

        // Print help screen
        self::$cmd();
    }

    /**
     * create
     */
    private static function create():void
    {

        Cli::sendHeader("create");
        Cli::send("Creates a new migration.\r\n\r\n");
        Cli::send("Usage:\r\n\r\n        apex-migrations create [--package <PACKAGE>] [--alias <ALIAS>]\r\n\r\n");
        Cli::send("--- OPTIONS ---\r\n\r\n");

        // Set options
        $options = [
            ['OPTION', 'REQUIRED', 'DESCRIPTION'], 
            ['--package', 'No', 'The package to create migration under.  Defaults to "default".'], 
            ['--alias', 'No', 'Optional alias, if defined migration class name will begin with this alias.  Otherwise, will default to `YYYMMDDHHIISS`.']
        ];

        // Display options
        foreach ($options as $vars) { 
            $line = str_pad($vars[0], 15, ' ', STR_PAD_RIGHT) . str_pad($vars[1], 10, ' ', STR_PAD_RIGHT) . $vars[2];
            Cli::send("$line\r\n");
        }
        Cli::send("\r\n");

    }

    /**
     * status
     */
    private static function status():void
    {

        Cli::sendHeader("status");
        Cli::send("View current status and all pending migrations awaiting installation.\r\n\r\n");
        Cli::send("Usage:\r\n\r\n        apex-migrations status [--package]\r\n\r\n");
        Cli::send("--- OPTIONS ---\r\n\r\n");

        // Set options
        $options = [
            ['OPTION', 'REQUIRED', 'DESCRIPTION'], 
            ['--package', 'No', 'The package to list pending migrations for.  If not specified, will list pending migrations for all packages.'] 
        ];

        // Display options
        foreach ($options as $vars) { 
            $line = str_pad($vars[0], 15, ' ', STR_PAD_RIGHT) . str_pad($vars[1], 10, ' ', STR_PAD_RIGHT) . $vars[2];
            Cli::send("$line\r\n");
        }
        Cli::send("\r\n");

    }

    /**
     * history
     */
    private static function history():void
    {

        Cli::sendHeader("history");
        Cli::send("View history of all previously installed migrations.  Run with no options to get list of all transactions.\r\n\r\n");
        Cli::send("Usage:\r\n\r\n        apex-migrations history [--package <PACKAGE>] [--txid <TXID>] [--limit X] [--start X] [--sort (asc|desc)]\r\n\r\n");
        Cli::send("--- OPTIONS ---\r\n\r\n");

        // Set options
        $options = [
            ['OPTION', 'REQUIRED', 'DESCRIPTION'], 
            ['--package', 'No', 'The package to list history for.'], 
            ['--txid', 'No', 'The transaction id# to list history of.  Get the id numbers by calling this command without any options.'], 
            ['--limit', 'No', 'The number of results to display.  Only applicable if run with no options, or with the --package option.'], 
            ['--start', 'No', 'Where in the result set to start.  Only applicable if run with no options, or with the --package option.'], 
            ['--sort', 'No', 'Order to sort results, can be either `desc` (default) or `asc`.  Only applicable if run with no options, or with the --package option.'], 
        ];

        // Display options
        foreach ($options as $vars) { 
            $line = str_pad($vars[0], 15, ' ', STR_PAD_RIGHT) . str_pad($vars[1], 10, ' ', STR_PAD_RIGHT) . $vars[2];
            Cli::send("$line\r\n");
        }
        Cli::send("\r\n");

    }

    /**
     * migrate
     */
    private static function migrate():void
    {

        Cli::sendHeader("migrate");
        Cli::send("Install migrations.  If run with no options, will install all pending migrations available for all packages.\r\n\r\n");
        Cli::send("Usage:\r\n\r\n        apex-migrations migrate [--package <PACKAGE>] [--name <NAME>]\r\n\r\n");
        Cli::send("--- OPTIONS ---\r\n\r\n");

        // Set options
        $options = [
            ['OPTION', 'REQUIRED', 'DESCRIPTION'], 
            ['--package', 'No', 'The package to install migrations for.'], 
            ['--name', 'No', 'The specific migration name to install.  Can be combined with `--package` to further specify which package the migration name exists in to avoid collision errors.']
        ];

        // Display options
        foreach ($options as $vars) { 
            $line = str_pad($vars[0], 15, ' ', STR_PAD_RIGHT) . str_pad($vars[1], 10, ' ', STR_PAD_RIGHT) . $vars[2];
            Cli::send("$line\r\n");
        }
        Cli::send("\r\n");

    }

    /**
     * rollback
     */
    private static function rollback():void
    {

        Cli::sendHeader("rollback");
        Cli::send("Rollback previously installed migrations.\r\n\r\n");
        Cli::send("Usage:\r\n\r\n        apex-migrations rollback [--txid <TXID>] [--package <PACKAGE>] [--last X] [--all]\r\n\r\n");
        Cli::send("--- OPTIONS ---\r\n\r\n");

        // Set options
        $options = [
            ['OPTION', 'REQUIRED', 'DESCRIPTION'], 
            ['--txid', 'No', 'The transaction id# to roll back up to and including.'], 
            ['--package', 'No', 'The package to rollback migrations on.'], 
            ['--last', 'No', 'if `--package` is defined, the last number of migrations installed on the package to rollback.  Otherwise, the number of transactions to rollback.'], 
            ['--all', 'No', 'Does not require a value, and if present along with the `--package` option will remove all migrations installed on that package.']
        ];

        // Display options
        foreach ($options as $vars) { 
            $line = str_pad($vars[0], 15, ' ', STR_PAD_RIGHT) . str_pad($vars[1], 10, ' ', STR_PAD_RIGHT) . $vars[2];
            Cli::send("$line\r\n");
        }
        Cli::send("\r\n");

    }





}


