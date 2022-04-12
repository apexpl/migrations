<?php
declare(strict_types = 1);

namespace Apex\Migrations\Adapters;

use Apex\Container\Di;
use Apex\Migrations\Cli\Cli;
use Apex\Db\Interfaces\DbInterface;
use Apex\Migrations\Exceptions\{MigrationsPackageNotExistsException, MigrationsClassNotExistsException};

/**
 * Apex Adapter
 */
class ApexAdapter implements AdapterInterface
{

    /**
     * Constructor
     */
    public function __construct(
            private DbInterface $db
    ) { 

    }

    /**
     * Create
     */
    public function create(string $dir_name, string $namespace, string $alias, string $branch, array $author = []):string
    {

        // Get alias
        if ($alias == '') { 
            $alias = 'Migration';
        }
        $alias = str_replace('-', '_', $alias);
        $class_name = 'm' . date('Ymd_His') . '_' . $alias;

        // Create directory
        $dir_name .= '/' . $class_name;
        if (!is_dir($dir_name)) { 
            mkdir($dir_name, 0755, true);
        }

        // Set replace
        $replace = [
            '~namespace~' => $namespace, 
            '~class_name~' => $class_name, 
            '~alias~' => $alias, 
            '~author_username~' => str_replace("'", "\\'", ($author['username'] ?? '')), 
            '~author_name~' => str_replace("'", "\\'", ($author['full_name'] ?? '')), 
            '~author_email~' => $author['email'] ?? '', 
            '~branch~' => $branch
        ];

        // Create files
        foreach (['migrate.php', 'install.sql', 'remove.sql'] as $file) { 
            $code = file_get_contents(__DIR__ . '/../../config/skel/apex/' . $file);
            $code = strtr($code, $replace);
            file_put_contents("$dir_name/$file", $code);
        }

        // Return
        return $dir_name;
    }

    /**
     * Install migration
     */
    public function install(string $class_name, string $dirname, string $namespace, array $entity_paths = [], bool $is_initial_install = false):int
    {

        // Load file
        require_once("$dirname/$class_name/migrate.php");

        // Load object
        $full_class = $namespace . "\\" . $class_name . "\\migrate";
        if (!class_exists($full_class)) { 
            throw new MigrationsClassNotExistsException("Migration class does not exist, $full_class");
        }
        $obj = Di::make($full_class);
        $start = hrtime(true);
        $this->db->closeCursors();

        // Check for initial install
        if ($is_initial_install === true) { 
            $include_install = $obj->include_with_initial_install ?? true;
            if ($include_install === false) { 
                return 0;
            }
        }

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

        // Return
        return $execute_ms;
    }

    /**
     * Rollback
     */
    public function rollback(string $class_name, string $namespace, string $dirname, array $entity_paths = []):void
    {

        // Load class
        require_once("$dirname/$class_name/migrate.php");

        // Load object
        $full_class = $namespace . "\\" . $class_name . "\\migrate";
        if (!class_exists($full_class)) { 
            throw new MigrationsClassNotExistsException("Migration class does not exist, $full_class");
        }

        $obj = Di::make($full_class);
        $this->db->closeCursors();

        // Pre-rollback, if needed
        if (method_exists($obj, 'preRemove')) { 
            $obj->preRemove($this->db);
        }

        // Rollback
        $obj->remove($this->db);

        // Post-rollback, if needed
        if (method_exists($obj, 'postRemove')) { 
            $obj->postRemove($this->db);
        }

    }

}


