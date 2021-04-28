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
class Create
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
        $alias = $opt['alias'] ?? '';
        $branch = $opt['branch'] ?? '';

        // Create new
        $dirname = $this->manager->create($package, $alias, $branch);

        // SEnd message
        Cli::send("Successfully created new migration, which is available at:\r\n\r\n      $dirname\r\n");
    }

}


