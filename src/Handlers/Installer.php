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
        private bool $send_output = true
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
    public function migratePackage(string $package, bool $is_initial_install = false):?array
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

            $installed[$class_name] = $this->installMigration($package, $class_name, $is_initial_install);
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
    public function installMigration(string $package, string $class_name, bool $is_initial_install = false):int
    {

        // Get adapter
        if (preg_match("/Eloquent\/(\d\d\d\d)_(\d\d)_(\d\d)_(\d\d)(\d\d)(\d\d)_/", $class_name, $m)) { 
            $adapter_class = "Apex\\Migrations\\Adapters\\EloquentAdapter";
            $revision = mktime((int) $m[4], (int) $m[5], (int) $m[6], (int) $m[2], (int) $m[3], (int) $m[1]);
            $type = 'eloquent';
        } elseif (preg_match("/Doctrine\/(.+)/", $class_name, $m)) { 
            $adapter_class = "Apex\\Migrations\\Adapters\\DoctrineAdapter";
            $revision = 0;
            $type = 'doctrine';
        } elseif (preg_match("/^m(\d\d\d\d)(\d\d)(\d\d)\_(\d\d)(\d\d)(\d\d)\_(.+)$/", $class_name, $m)) { 
            $adapter_class = "Apex\\Migrations\\Adapters\\ApexAdapter";
            $revision = mktime((int) $m[4], (int) $m[5], (int) $m[6], (int) $m[2], (int) $m[3], (int) $m[1]);
            $type = 'apex';
        } else { 
            throw new MigrationsInvalidArgumentException("Invalid migration class name, $class_name");
        }

        // Get package info
        if (!list($dirname, $namespace, $entity_paths) = $this->config->getPackage($package)) { 
            throw new MigrationsPackageNotExistsException("Package does not exist, $package");
        }

        // Load adapter
        $adapter = Di::make($adapter_class);
        $execute_ms = $adapter->install($class_name, $dirname, $namespace, $entity_paths, $is_initial_install);

        // Add to db
        $table_name = $this->config->getTableName();
        $this->db->insert($table_name, [
            'transaction_id' => $this->getTransactionId(), 
            'type' => $type,
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

    /**
     * Set send output
     */
    public function setSendOutput(bool $send_output):void
    {
        $this->send_output = $send_output;
    }

}


