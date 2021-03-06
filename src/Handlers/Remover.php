<?php
declare(strict_types = 1);

namespace Apex\Migrations\Handlers;

use Apex\Migrations\Config;
use Apex\Migrations\Cli\Cli;
use Apex\Migrations\Exceptions\{MigrationsPackageNotExistsException, MigrationsClassNotExistsException};
use Apex\Db\Interfaces\DbInterface;
use Apex\Container\Di;

/**
 * Rollback / remove migrations
 */
class Remover
{

    /**
     * Constructor
     */
    public function __construct(
        private Config $config, 
        private DbInterface $db, 
        private bool $send_output = true
    ) {

    }

    /**
     * Rollback by txid
     */
    public function rollbackTransaction(int $txid):void
    {

        // Go through all migrations
        $table_name = $this->config->getTableName();
        $rows = $this->db->query("SELECT * FROM $table_name WHERE transaction_id = %i ORDER BY id DESC", $txid);
        foreach ($rows as $row) { 
            $this->removeMigration($row['package'], $row['class_name']);
        }

    }

    /**
     * Rollback last X transactions
     */
    public function rollbackLastTransaction(int $last = 1):void
    {

        // Get last transactions
        $table_name = $this->config->getTableName();
        $txids = $this->db->getColumn("SELECT DISTINCT(transaction_id) FROM $table_name ORDER BY transaction_id DESC LIMIT $last");
        foreach ($txids as $txid) { 
            $this->rollbackTransaction((int) $txid);
        }

    }

    /**
     * Rollback last package
     */
    public function rollbackLastPackage(string $package, int $last = 1):void
    {

        // Go through migrations
        $table_name = $this->config->getTableName();
        $rows = $this->db->query("SELECT * FROM $table_name WHERE package = %s ORDER BY id DESC LIMIT $last", $package);
        foreach ($rows as $row) { 
            $this->removeMigration($row['package'], $row['class_name']);
        }

    }


    /**
     * Remove package
     */
    public function removePackage(string $package):void
    {

        // GO through all migrations of package
        $table_name = $this->config->getTableName();
        $rows = $this->db->query("SELECT * FROM $table_name WHERE package = %s ORDER BY id DESC", $package);
        foreach ($rows as $row) { 
            $this->removeMigration($row['package'], $row['class_name']);
        }

    }

    /**
     * Remove migration
     */
    public function removeMigration(string $package, string $class_name):void
    {

        // Get migration from db
        $table_name = $this->config->getTableName();
        if (!$row = $this->db->getRow("SELECT * FROM $table_name WHERE package = %s AND class_name = %s", $package, $class_name)) { 
            throw new MigrationsClassNotExistsException("The package name '$class_name' in package '$package' does not exist, hence can not be removed.");
        }

        // Get info
        if (!list($dirname, $namespace, $entity_paths) = $this->config->getPackage($package)) { 
            throw new MigrationsPackageNotExistsException("Package does not exist, $package");
        }

        // Load adapter
        $adapter_class = "Apex\\Migrations\\Adapters\\" . ucwords($row['type']) . "Adapter";
        $adapter = Di::make($adapter_class);

        // Send message
        Cli::send("Removing migration $class_name from package $row[package]\r\n");

        // Remove migration
        $adapter->rollback($class_name, $namespace, $dirname, $entity_paths); 

        // Delete from db
        $this->db->query("DELETE FROM $table_name WHERE package = %s AND class_name = %s", $package, $class_name);
    }

}



