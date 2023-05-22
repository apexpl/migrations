<?php
declare(strict_types = 1);

namespace Apex\Migrations\Adapters;

use Apex\Container\Di;
use Apex\Migrations\Config;
use Apex\Migrations\Handlers\Io;
use Apex\Db\Interfaces\DbInterface;
use Apex\Migrations\Exceptions\{MigrationsClassNotExistsException, MigrationsPackageNotExistsException};
use Doctrine\Migrations\Generator\{DiffGenerator, Generator, SqlGenerator};
use Doctrine\ORM\Mapping\Table;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\SchemaDumper;

/**
 * Doctrine adapter
 */
class DoctrineAdapter implements AdapterInterface
{

    /**
     * Constructor
     */
    public function __construct(
        private ?DbInterface $db,
        private Io $io,
        private Config $config
    ) { 

    }

    /**
     * Create
     */
    public function create(string $dir_name, string $namespace, string $alias, string $branch, array $author = []):string
    {

        // Get filename
        $class_name = 'Version' . date('YmdHis');
        $dir_name .= '/Doctrine';

        // Create directory
        if (!is_dir($dir_name)) { 
            mkdir($dir_name, 0755, true);
        }

        // Set replace
        $replace = [
            '~namespace~' => $namespace, 
            '~class_name~' => $class_name
        ];

        // Generate and save code
        $code = file_get_contents(__DIR__ . '/../../config/skel/doctrine.php');
        $code = strtr($code, $replace);
        file_put_contents("$dir_name/$class_name.php", $code);

        // Return
        return "$dir_name/$class_name.php";
    }

    /**
     * Install
     */
    public function install(string $filename, string $dirname, string $namespace, array $entity_paths = [], bool $is_initial_install = false):int
    {

        // Check filename
        if (!preg_match("/^Doctrine\/(.+?)$/", $filename, $m)) { 
        throw new MigrationsClassNotExistsException("Invalid Doctrine migration filename, $filename");
        }
        $class_name = $namespace . "\\Doctrine\\" . $m[1];

        // Load file
        require_once("$dirname/$filename.php");

        // Check class exists
        if (!class_exists($class_name)) { 
            throw new MigrationsClassNotExistsException("Doctrine migration class does not exist at, $class_name");
        }

        // Get Doctrine managed tables
        $tables = $this->getDoctrineTables($entity_paths);

        // Get Doctrine db
        $doctrine = \Apex\Db\Wrappers\Doctrine::init($this->db);
        $doctrine->getConfiguration()->setFilterSchemaAssetsExpression('/' . implode('|', $tables) . '/');
        $connection = $doctrine->getConnection();
        $schema = $connection->getSchemaManager()->createSchema();

        // Load migration
        $obj = Di::make($class_name, ['connection' => $connection]);
        $start = hrtime(true);

        // Install
        $obj->preUp($schema);
        $obj->up($schema);
        $obj->postUp($schema);

        // Get SQL
        $queries = $obj->getSql();
        foreach ($queries as $sql) { 
            $stmt = $sql->getStatement();
            $this->db->query($stmt);
        }

        // Return
        $execute_ms = (int) ((hrtime(true) - $start) / 1000000);
        return $execute_ms;
    }

    /**
     * Get table names managed by Doctrine
     */
    private function getDoctrineTables(array $entity_paths):array
    {

        // Go through entity paths
        $tables = [];
        foreach ($entity_paths as $path) { 

            // Check dir exists
            if (!is_dir($path)) { 
                continue;
            }
            $files = scandir($path);

            // Go though files
            foreach ($files as $file) { 

                if (!preg_match("/\.php$/", $file)) { 
                    continue;
                }
                $code = file_get_contents("$path/$file");

                // Get fqdn
                $filename = trim(str_replace(SITE_PATH, '', "$path/$file"), '/');
                list($fqdn, $short_name) = $this->pathToNamespace($filename);
                $fqdn .= "\\$short_name";

                // Get attributes
                $obj = new \ReflectionClass($fqdn);
                $attributes = $obj->getAttributes();

                // Go through attributes
                foreach ($attributes as $attr) {

                    if ($attr->getName() == Table::class) {
                        $args = $attr->getArguments();
                        $tables[] = $args['name'];
                    }
                }
            }
        }

        // Return
        return $tables;
    }

    /**
     * Diff
     */
    public function diff(string $pkg_alias):string
    {

        // Get package info
        if (!list($dirname, $namespace, $entity_paths) = $this->config->getPackage($pkg_alias)) { 
            throw new MigrationsPackageNotExistsException("Package does not exist, $pkg_alias");
        }

        // Create directory, if needed
        if (!is_dir("$dirname/Doctrine")) { 
            mkdir("$dirname/Doctrine", 0755, true);
        }

        // Get Doctrine managed tables
        $tables = $this->getDoctrineTables($entity_paths);

        // Get Doctrine db
        $doctrine = \Apex\Db\Wrappers\Doctrine::init($this->db, $entity_paths);
        $doctrine->getConfiguration()->setFilterSchemaAssetsExpression('/' . implode('|', $tables) . '/');

        // Create migrations configuration
        $configuration = new Configuration($doctrine->getConnection());
        $configuration->addMigrationsDirectory($namespace . "\\Doctrine", "$dirname/Doctrine");
        $configuration->setAllOrNothing(true);
        $configuration->setCheckDatabasePlatform(false);

        // Load diff generator
        $diff = new DiffGenerator(
            $doctrine->getConnection()->getConfiguration(),
            $doctrine->GetConnection()->getSchemaManager(),
            new OrmSchemaProvider($doctrine),
            $doctrine->getConnection()->getDatabasePlatform(),
            new Generator($configuration),
            new SqlGenerator($configuration, $doctrine->getConnection()->getDatabasePlatform()),
            new OrmSchemaProvider($doctrine)
        );

        // Generate migration
        $class_name = $namespace . "\\Doctrine\\Version" . date('YmdHis');
        $filename = $diff->generate($class_name, '/' . implode('|', $tables) . '/', false, 120, false, false);
        return $filename;
    }

    /**
     * Dump schema
     */
    public function dump(string $pkg_alias):string
    {

        // Get package info
        if (!list($dirname, $namespace, $entity_paths) = $this->config->getPackage($pkg_alias)) { 
            throw new MigrationsPackageNotExistsException("Package does not exist, $pkg_alias");
        }

        // Create directory, if needed
        if (!is_dir("$dirname/Doctrine")) { 
            mkdir("$dirname/Doctrine", 0755, true);
        }

        // Get Doctrine managed tables
        $tables = $this->getDoctrineTables($entity_paths);

        // Get Doctrine db
        $doctrine = \Apex\Db\Wrappers\Doctrine::init($this->db, $entity_paths);
        //$doctrine->getConfiguration()->setSchemaAssetsFilter('/' . implode('|', $tables) . '/');

        // Get connection
        $conn_opts = [
            'pdo' => $this->db->connect_mgr->getConnection('write'),
            'driver' => 'pdo_mysql'
        ];
        $connection = DriverManager::getConnection($conn_opts);

        // Create migrations configuration
        $configuration = new Configuration($connection);
        $configuration->addMigrationsDirectory($namespace . "\\Doctrine", "$dirname/Doctrine");
        $configuration->setAllOrNothing(true);
        $configuration->setCheckDatabasePlatform(false);

$doctrine->getConnection()->executeQuery("CREATE TABLE test (id INT, name VARCHAR(100))");
echo "Exected\n"; exit;
$m = get_class_methods($connection);
print_r($m); exit;
$p = $connection->getDatabasePlatform();
$p = $doctrine->getConnection()->getDatabasePlatform();
echo "Got p\n"; exit;
var_dump($p); exit;
        // Create schema dumper
        $dumper = new SchemaDumper(
            $doctrine->getConnection()->getDatabasePlatform(),
            $doctrine->GetConnection()->createSchemaManager(),
            new Generator($doctrine->getConfiguration()),
            new SqlGenerator($configuration, $doctrine->getConnection()->getDatabasePlatform())
        );
echo "Got dumper\n"; exit;
        // Dump schema
        $class_name = $namespace . "\\Doctrine\\Version" . date('YmdHis');
        $filename = $dumper->dump($class_name);
        return $filename;
    }

    /**
     * Rollback
     */
    public  function rollback(string $filename, string $namespace, string $dirname, array $entity_paths = []):void
    {

        // Check filename
        if (!preg_match("/^Doctrine\/(.+?)$/", $filename, $m)) { 
        throw new MigrationsClassNotExistsException("Invalid Doctrine migration filename, $filename");
        }
        $class_name = $namespace . "\\Doctrine\\" . $m[1];

        // Load file
        require_once("$dirname/$filename.php");

        // Check class exists
        if (!class_exists($class_name)) { 
            throw new MigrationsClassNotExistsException("Doctrine migration class does not exist at, $class_name");
        }

        // Get Doctrine managed tables
        $tables = $this->getDoctrineTables($entity_paths);

        // Get Doctrine db
        $doctrine = \Apex\Db\Wrappers\Doctrine::init($this->db);
        $doctrine->getConfiguration()->setFilterSchemaAssetsExpression('/' . implode('|', $tables) . '/');
        $connection = $doctrine->getConnection();
        $schema = $connection->getSchemaManager()->createSchema();

        // Load migration
        $obj = Di::make($class_name, ['connection' => $connection]);

        // Rollback
        $obj->preDown($schema);
        $obj->down($schema);
        $obj->postDown($schema);

        // Get SQL
        $queries = $obj->getSql();
        foreach ($queries as $sql) { 
            $stmt = $sql->getStatement();
            $this->db->query($stmt);
        }

    }

    /**
     * Path to Namespace
     */
    private static function pathToNamespace(string $filename):array
    {

        // Trim excess
        $filename = preg_replace("/^src\//", "", trim($filename, '/'));
        $filename = preg_replace("/\.php$/", "", $filename);

        // Get names
        $parts = explode("/", $filename);
        $class_name = array_pop($parts);
        $namespace = "App\\" . implode("\\", $parts);

        // Return
        return [$namespace, $class_name];
    }


}


