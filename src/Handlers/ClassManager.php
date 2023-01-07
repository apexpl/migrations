<?php
declare(strict_types = 1);

namespace Apex\Migrations\Handlers;

use Apex\Container\Di;
use Apex\Migrations\{Config, Migrations};
use Apex\Db\Interfaces\DbInterface;
use Apex\Migrations\Handlers\Io;
use Apex\Migrations\Exceptions\{MigrationsPackageNotExistsException, MigrationsDirectoryException};

/**
 * Migration classes
 */
class ClassManager
{

    /**
     * Constructor
     */
    public function __construct(
        private Config $config, 
    private Migrations $migrations
    ) { 

    }

    /**
     * Create new class
     */
    public function create(string $package = '', string $alias = '', string $branch = '', string $type = 'apex'):string
    {

        // Get default package
        if ($package == '') { 
            $package = 'default';
        }

        // Get package from config
        if (!list($dirname, $namespace) = $this->config->getPackage($package)) { 
            throw new MigrationsPackageNotExistsException("Package does not exist within YAML configuration file, $package");
        }
        $dirname = str_replace('~package~', $package, $dirname);
        $author = $this->config->getAuthor();

        // Load adapter
        $adapter_class = "\\Apex\\Migrations\\Adapters\\" . ucwords($type) . 'Adapter';
        $adapter = Di::make($adapter_class);

        // Create
        $res = $adapter->create($dirname, $namespace, $alias, $branch, $author);

        // Return
        return $res;
    }

    /**
     * Scan package directory
     */
    public function scanPackageDirectory(string $package):array
    {

        // Initialize
        $db = Di::get(DbInterface::class);
        $table_name = $this->config->getTableName();

        // Get directory
        if (!list($dirname, $namespace) = $this->config->getPackage($package)) { 
            throw new MigrationsPackageNotExistsException("Package does not exist, $package");
        }

        // Start results
        $res = [
            'latest' => [
                'revision' => 0, 
                'class_name' => 'No Migrations Installed', 
                'installed_at' => 'Never', 
                'execute_ms' => 0
            ], 
            'installed' => [], 
            'pending' => []
        ];

        // Check directory exists
        if (!is_dir($dirname)) { 
            return $res;
        }

        // Scan directory for files
        $io = Di::make(Io::class);
        $files = $io->parseDir($dirname, true);

        // GO through files
        foreach ($files as $file) {

            // Check if valid migration class
            if (preg_match("/^(Eloquent|Doctrine)\/(.+?)\.php$/", $file, $match)) { 
                $class_name = $match[1] . "/" . $match[2];
            } elseif (preg_match("/^m(\d{8})\_(\d{6})\_(.+)$/", $file, $match) && file_exists("/$dirname/$file/migrate.php")) { 
                $class_name = $file;
            } else { 
                continue;
            }

            // Check database
            if ($row = $db->getRow("SELECT * FROM $table_name WHERE class_name = %s", $class_name)) { 
                $res['installed'][$row['revision']] = $row['class_name'] . ' installed on ' . $row['installed_at'] . ' (' . (int) $row['execute_ms'] . ' ms)';
                if ($row['revision'] > $res['latest']['revision']) { 
                    $res['latest']['revision'] = (int) $row['revision'];
                    $res['latest']['class_name'] = $row['class_name'];
                    $res['latest']['installed_at'] = $row['installed_at'];
                    $res['latest']['execute_ms'] = $row['execute_ms'];
                }
                continue;
            }

            // Add to pending
            $res['pending'][$match[2]] = $class_name;
        }

        // Sort arrays
        ksort($res['pending']);
        krsort($res['installed']);

        // Return
        return $res;
    }

    /**
     * Search by migration name
     */
    public function searchByName(string $name):?array
    {

        // Init
        $results = [];
        $packages = $this->config->getPackages();

        // Go through packages
        foreach ($packages as $alias => $vars) { 

            // Check if file exists
            $file = $vars['dir'] . '/' . $name . '/migrate.php';
            if (file_exists($file)) { 
                $results[] = $alias;
            }
        }

        // Return
        return count($results) == 0 ? null : $results;
    }

}

