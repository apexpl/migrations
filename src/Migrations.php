<?php
declare(strict_types = 1);

namespace Apex\Migrations;

use Apex\Migrations\Config;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;
use Apex\Debugger\Interfaces\DebuggerInterface;
use redis;

/**
 * Migrations
 */
class Migrations
{

    /**
     * Constructor
     */
    public function __construct(
        ?string $container_file = '', 
        string $yaml_file = ''
    ) { 

        // Setup container
        if ($container_file !== null) {
            $this->setupContainer($container_file);
        }

        // Load YAML config
        $config = Di::makeset(Config::class, ['yaml_file' => $yaml_file]);

        // Check migrations table
        $this->checkMigrationsTable();
    }

    /**
     * Check migrations table
     */
    public function checkMigrationsTable():void
    {

        // Get items from container
        $db = Di::get(DbInterface::class);
        $config = Di::get(Config::class);

        // Chek table name
        $table_name = $config->getTableName();
        if ($db->checkTable($table_name) === true) { 
            return;
        }

        // Create table
        $sql = str_replace('~table_name~', $table_name, trim(file_get_contents(__DIR__ . '/../config/setup.sql')));
        $db->query($sql);
        $db->clearCache();
    }

    /**
     * Setup container
     */
    private function setupContainer(string $container_file):void
    {

        // Check file
        if ($container_file == '') { 
            $container_file = __DIR__ . '../config/container.php';
        }

        // Ensure container file exists
        if (!file_exists($container_file)) { 
            return;
        }

        // Build container
        Di::buildContainer($container_file);
        Di::set(__CLASS__, $this);
        Di::markItemAsService(DbInterface::class);

        // Mark redis as service, if needed
        if (Di::has(redis::class)) { 
            Di::markItemAsService(redis::class);
        }
        if (Di::has(DebuggerInterface::class)) { 
            Di::markItemAsService(DebuggerInterface::class);
        }
    }

    /**
     * Format seconds
     */
    public function formatSecs(int $ms):string
    {

        // Return, if less than 1000
        if ($ms < 1000) { 
            return (string) $ms . 'ms';
        }
        $ms /= sprintf("%.2f", 1000);

        // Skip, if less than 60 seconds
        if ($ms < 60) { 
            return (string) $ms . ' seconds';
        }

        // Return minutes
        $ms = sprintf("%.2f", $ms / 60);
        return (string) $ms . ' minutes';
    }
}


