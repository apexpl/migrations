<?php
declare(strict_types = 1);

namespace Apex\Migrations\Handlers;

use Apex\Container\Di;
use Apex\Migrations\{Config, Migrations};
use Apex\Db\Interfaces\DbInterface;
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
    public function create(string $package = '', string $alias = '', string $branch = ''):string
    {

        // Get default package
        if ($package == '') { 
            $package = 'default';
        }

        // Get alias
        if ($alias == '') { 
            $alias = date('Ymd_His');
        }
        $class_name = $alias . '_' . time();

        // Get package from config
        if (!list($dirname, $namespace) = $this->config->getPackage($package)) { 
            throw new MigrationsPackageNotExistsException("Package does not exist within YAML configuration file, $package");
        }
        $dirname = str_replace('~package~', $package, $dirname) . '/' . $class_name;
        $author = $this->config->getAuthor();

        // Create directory
        mkdir($dirname);

        // Set replace
        $replace = [
            '~namespace~' => $namespace, 
            '~class_name~' => $class_name, 
            '~package~' => $package, 
            '~alias~' => $alias, 
            '~author_username~' => str_replace("'", "\\'", ($author['username'] ?? '')), 
            '~author_name~' => str_replace("'", "\\'", ($author['full_name'] ?? '')), 
            '~author_email~' => $author['email'] ?? '', 
            '~branch~' => $branch
        ];

        // Create files
        foreach (['migrate.php', 'install.sql', 'rollback.sql'] as $file) { 
            $code = file_get_contents(__DIR__ . '/../../config/skel/' . $file);
            $code = strtr($code, $replace);
            file_put_contents("$dirname/$file", $code);
        }

        // Return
        return $dirname;
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

        // Open directory
        if (!$handle = opendir($dirname)) { 
            throw new MigrationsDirectoryException("Unable to open '$package' package directory at $dirname");
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

        // GO through files
        while ($file = readdir($handle)) { 

            // Check file name format
            if (!preg_match("/^(.+)\_(\d{10})$/", $file, $match)) { 
                continue;
            } elseif (!file_exists("/$dirname/$file/migrate.php")) { 
                continue;
            }
            $class_name = $match[1] . '_' . $match[2];

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

