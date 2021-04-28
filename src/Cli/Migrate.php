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
class Migrate
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

        // Initialize
        if (!list($package, $name) = $this->getOptions()) { 
            return;
        }
        $installer = Di::make(Installer::class, ['send_output' => true]);

        // Migrate as necessary
        if ($name != '') { 
            $secs = $installer->installMigration($package, $name);
            Cli::send("Installed migration $name from package '$package' in " . $secs . "ms\n\n");

        } elseif ($package == '') { 
            $installer->migrateAll();
        } elseif (!$installer->migratePackage($package)) { 
            Cli::send("Nothing to do.  Package '$package' is up to date.\n");
        }

    }

    /**
     * Get options
     */
    private function getOptions():?array
    {

        // Get options
        list($args, $opt) = Cli::getArgs(['package', 'name']);
        $package = $opt['package'] ?? '';
        $name = $opt['name'] ?? '';
        $table_name = $this->config->getTableName();

        // Check package exists, if defined
        if ($package != '' && !$pkg = $this->config->getPackage($package)) { 
            Cli::send("Package does not exist '$package'.  No migrations installed.\n");
            return null;
        }

        // Search name within all packages
        if ($name != '' && $package == '') { 

            if (!$packages = $this->manager->searchByName($name)) { 
                Cli::send("The migration '$name' does not exist within any packages.  No migrations installed.\n");
                return null;
            } elseif (count($packages) > 1) { 
                Cli::send("The migration '$name' exists in more than one package.  Please use the --package option to specify which package to install from.  No migrations installed.\n");
                return null;
            }
            $package = $packages[0];

        // Check name with package defined
        } elseif ($name != '' && !file_exists($pkg[0] . '/' . $name . '/migrate.php')) {  
            Cli::send("No migration exists with package '$package' and name '$name'.  No migrations installed.\n");
            return null;
        }

        // Check if already installed
        if ($name != '' && $package != '' && $row = $this->db->getRow("SELECT * FROM $table_name WHERE package = %s AND class_name = %s", $package, $name)) { 
            Cli::send("The migration for package '$package' with name '$name' has already been installed, and can not be installed again.\n");
            return null;
        }

        // Return
        return [$package, $name];
    }


}

