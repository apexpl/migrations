<?php
declare(strict_types = 1);

namespace Apex\Migrations\Adapters;

use Apex\Container\Di;
use Illuminate\Database\Console\Migrations\TableGuesser;
use Apex\Db\Interfaces\DbInterface;
use Apex\Migrations\Exceptions\MigrationsClassNotExistsException;

/**
 * Eloquent adapter
 */
class EloquentAdapter implements AdapterInterface
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

        // Initial checks
        if ($alias == '' || !preg_match("/^[a-zA-Z0-9-_]+$/", $alias)) { 
            throw new \InvalidArgumentException("Invalid migration alias specified, $alias");
        }

        // Get skel file and table name
        if (!$guess = TableGuesser::guess($alias)) { 
            $table_name = '';
            $skel_file = 'default.php';
        } else { 
            $table_name = $guess[0];
            $skel_file = $guess[1] === true ? 'create.php' : 'update.php';
        }

        // Get filename
        $filename = date('Y_m_d_His') . '_' . $alias . '.php';
        $dir_name .= '/Eloquent';

        // Create directory
        if (!is_dir($dir_name)) { 
            mkdir($dir_name, 0755, true);
        }

        // Set replace
        $replace = [
            '~namespace~' => $namespace, 
            '~class_name~' => $alias, 
            '~table_name~' => $table_name
        ];

        // Generate and save code
        $code = file_get_contents(__DIR__ . '/../../config/skel/eloquent/' . $skel_file);
        $code = strtr($code, $replace);
        file_put_contents("$dir_name/$filename", $code);

        // Return
        return "$dir_name/$filename";
    }

    /**
     * Install
     */
    public function install(string $filename, string $dirname, string $namespace, array $entity_paths = []):int
    {

        // Get class name
        if (!preg_match("/Eloquent\/\d\d\d\d_\d\d_\d\d_\d\d\d\d\d\d_(.+)$/", $filename, $match)) { 
            throw new MigrationsClassNotExistsException("Invalid Eloquent class name, $filename");
        }
        $class_name = $match[1];

        // Load file
        require_once("$dirname/$filename.php");

        // Load class
        $class_name = $namespace . "\\Eloquent\\" . $class_name;
        if (!class_exists($class_name)) { 
            throw new MigrationsClassNotExistsException("Eloquent migration class does not exist at, $class_name");
        }
        $obj = Di::make($class_name);
        $start = hrtime(true);

        // Boot Eloquent
        $connection = \Apex\Db\Wrappers\Eloquent::init($this->db);
        $connection->bootEloquent();
        $connection->setAsGlobal();
        $schema = $connection->schema();

        // Install
        $obj->up($schema);

        // Return
        $execute_ms = (int) ((hrtime(true) - $start) / 1000000);
        return $execute_ms;
    }

    /**
     * Rollback
     */
    public function rollback(string $filename, string $namespace, string $dirname, array $entity_paths = []):void
    {

        // Get class name
        if (!preg_match("/Eloquent\/\d\d\d\d_\d\d_\d\d_\d\d\d\d\d\d_(.+)$/", $filename, $match)) { 
            throw new MigrationsClassNotExistsException("Invalid Eloquent class name, $filename");
        }
        $class_name = $match[1];

        // Load file
        require_once("$dirname/$filename.php");

        // Load class
        $class_name = $namespace . "\\Eloquent\\" . $class_name;
        if (!class_exists($class_name)) { 
            throw new MigrationsClassNotExistsException("Eloquent migration class does not exist at, $class_name");
        }
        $obj = Di::make($class_name);

        // Boot Eloquent
        $connection = \Apex\Db\Wrappers\Eloquent::init($this->db);
        $connection->bootEloquent();
        $connection->setAsGlobal();
        $schema = $connection->schema();

        // Rollback
        $obj->down($schema);
    }

}


