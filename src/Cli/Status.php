<?php
declare(strict_types = 1);

namespace Apex\Migrations\Cli;

use Apex\Migrations\{Config, Migrations};
use Apex\Migrations\Handlers\{ClassManager, Installer};
use Apex\Migrations\Cli\Cli;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;


/**
 * Migrate CLI command
 */
class Status
{

    /**
     * Constructor
     */
    public function __construct(
        private Config $config, 
        private ClassManager $manager, 
        private DbInterface $db
    ) { 

    }

    /**
     * Process
     */
    public function process():void
    {

        // Get options
        list($args, $opt) = Cli::getArgs(['alias', 'package','branch']);
        $package = $opt['package'] ?? '';

        // Get packages
        if ($package == '') { 
            $packages = array_keys($this->config->getPackages());
        } else { 
            $packages = [$package];
        }

        // Go through packages
        $total=0;
        foreach ($packages as $package) { 

            // Scan directory
            $res = $this->manager->scanPackageDirectory($package);
            if (count($res['pending']) == 0) { 
                continue;
            }

            // Show pending
            Cli::send("\r\nFound " . count($res['pending']) . " pending migrations for package " . $package . ":\r\n");
            foreach ($res['pending'] as $secs => $name) { 
                Cli::send("      $name\r\n");
            }
            $total += count($res['pending']);
        }
        // Send response
        if ($total > 0) {
            Cli::send("\r\nThere are a total of $total pending migrations awaiting installation.  You may install all pending migrations with:\r\n\r\n");
            Cli::send("      apex-migrations migrate\r\n\r\n");
        } else {
            Cli::send("No pending migrations found.  Database is up to date.\r\n\r\n");
        }

    }

}


