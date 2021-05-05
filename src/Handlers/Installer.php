<?php
declare(strict_types = 1);

namespace Apex\Migrations\Handlers;

use Apex\Migrations\{Config, Migrations};
use Apex\Migrations\Handlers\ClassManager;
use Apex\Migrations\Cli\Cli;
use Apex\Db\Interfaces\DbInterface;
use Apex\Container\Di;
use Apex\Migrations\Exceptions\{MigrationsPackageNotExistsException, MigrationsClassNotExistsException, MigrationsInvalidArgumentException};

/**
 * Processor
 */
class Installer
{

    // Properties
    private int $transaction_id = 0;

    /**
     * Constructor
     */
    public function __construct(
        private Config $config,
        private Migrations $migrations,  
        private DbInterface $db, 
        private bool $send_output = false
    ) { 

    }

    /**
     * Migrate all packages
     */
    public function migrateAll():array
    {

        // Initialize
        $packages = $this->config->getPackages();
        $total_ms = 0;
        $total = 0;
        $installed = [];

        // Go through packages
        foreach ($packages as $package => $vars) { 

            // Migrate package
            if (!$in = $this->migratePackage($package)) { 
                continue;
            }

            // Add to totals
            $installed[$package] = $in;
            $total_ms += array_sum(array_values($in));
            $total += count($in);
        }

        // Send output, if needed
        if ($this->send_output === true && $total == 0) { 
            Cli::send("\nNothing to do.  Database is up to date.\n");
        } elseif ($this->send_output === true) { 
            $secs = $this->migrations->formatSecs($total_ms);
            Cli::send("\nFinished total $total migrations installed in $secs.  Database is up to date.\n");
        }

        // Return
        return $installed;
    }

    /**
     * Migrate a package
     */
    public function migratePackage(string $package):?array
    {

        // Scan directory
        $manager = Di::make(ClassManager::class);
        $res = $manager->scanPackageDirectory($package);
        ksort($res['pending']);

        // Return if no migrations
        if (count($res['pending']) == 0) { 
            return null;
        }

        // Send header, if needed
        if ($this->send_output === true) { 
            Cli::sendHeader("Package: $package");
        }

        // Go through all pending migrations
        $installed = [];
        foreach ($res['pending'] as $revision => $class_name) { 
            $installed[$class_name] = $this->installMigration($package, $class_name);
            if ($this->send_output === true) { 
                Cli::send("Installed migration $class_name in " . $installed[$class_name] . "ms\n");
            }
        }

        // Return
        return $installed;
    }

    /**
     * Install migration
     */
    public function installMigration(string $package, string $class_name):int
    {

        // Verify class name format
        if (!preg_match("/^(.+)\_(\d{10})$/", $class_name, $match)) { 
            throw new MigrationsInvalidArgumentException("Invalid migration class name, $class_name");
        }
        $revision = (int) $match[2];

        // Get info
        if (!list($dirname, $namespace) = $this->config->getPackage($package)) { 
            throw new MigrationsPackageNotExistsException("Package does not exist, $package");
        }
        require_once("$dirname/$class_name/migrate.php");

        // Load object
        $full_class = $namespace . "\\" . $class_name . "\\migrate";
        if (!class_exists($full_class)) { 
            throw new MigrationsClassNotExistsException("Migration class does not exist, $full_class");
        }
        $obj = Di::make($full_class);
        $start = hrtime(true);
        $this->db->closeCursors();

        // Pre-install, if needed
        if (method_exists($obj, 'preInstall')) { 
            $obj->preInstall($this->db);
        }

        // Install
        $obj->install($this->db);

        // Post-install, if needed
        if (method_exists($obj, 'postInstall')) { 
            $obj->postInstall($this->db);
        }
        $execute_ms = (int) ((hrtime(true) - $start) / 1000000);

        // Add to db
        $table_name = $this->config->getTableName();
        $this->db->insert($table_name, [
            'transaction_id' => $this->getTransactionId(), 
            'package' => $package,  
            'revision' => $revision, 
            'class_name' => $class_name, 
            'execute_ms' => $execute_ms
        ]);

        // Return
        return $execute_ms;
    }

    /**
     * Get transaction id
     */
    private function getTransactionId():int
    {

        // Check if we have id
        if ($this->transaction_id > 0) { 
            return $this->transaction_id;
        }

        // Get transaction id
        $table_name = $this->config->getTableName();
        if (!$id= $this->db->getField("SELECT max(transaction_id) FROM $table_name")) { 
            $id = 0;
        }
        $this->transaction_id = ($id + 1);

        // Return
        return $this->transaction_id;
    }

}


