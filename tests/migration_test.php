<?php
declare(strict_types = 1);

use Apex\Migrations\{Migrations, Config};
use Apex\Migrations\Handlers\{ClassManager, Installer, Remover};
use PHPUnit\Framework\TestCase;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;


/**
 * Boolean test
 */
class migrations_test extends TestCase
{

    /**
     * Test init
     */
    public function test_init()
    {

        // Init
        $migrations = new Migrations(
            container_file: __DIR__ . '/files/container.php'
        );

        // Check config
        $config = Di::get(Config::class);
        $table_name = $config->getTableName();
        $this->assertEquals('test_migrations', $table_name);

        // Delete table
        $db = Di::get(DbInterface::class);
        $db->query("DROP TABLE IF EXISTS test_migrations");

        // Setup migrations dir
        $dir = __DIR__ . '/files/migrations';
        if (is_dir($dir)) { system("rm -rf $dir"); }
        mkdir($dir);
    }

    /**
     * Test init
     */
    public function test_create()
    {

        // Create
        $migrations = new Migrations(
            container_file: __DIR__ . '/files/container.php'
        );

        // Check config
        $config = Di::get(Config::class);
        $table_name = $config->getTableName();
        $this->assertEquals('test_migrations', $table_name);

        // Create
        $manager = Di::make(ClassManager::class);
        $dirname = $manager->create();
        $this->assertDirectoryExists($dirname);
        $this->assertFileExists("$dirname/migrate.php");

        // Chek table
        $db = Di::get(DbInterface::class);
        $db->clearCache();
        $tables = $db->getTableNames();
        $this->assertContains('test_migrations', $tables);

        // Save install.sql
        file_put_contents("$dirname/install.sql", "\n\nCREATE TABLE mig_test (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL);\n\n");
        file_put_contents("$dirname/rollback.sql", "\n\nDROP TABLE mig_test;\n\n");
        $db->query("DROP TABLE IF EXISTS mig_test");

        // Check tables
        $db->clearCache();
        $this->assertFalse($db->checkTable('mig_test'));

        // Install
        $installer = Di::make(Installer::class);
        $installer->migrateAll();

        // Chek table
        $db->clearCache();
        $this->assertTrue($db->checkTable('mig_test'));

        // Remove
        $remover = Di::make(Remover::class);
        $remover->rollbackLastTransaction(1);

        // Check table
        $db->clearCache();
        $this->assertFalse($db->checkTable('mig_test'));

        // Clearn up
        $db->query("DROP TABLE IF EXISTS test_migrations");
        $dir = __DIR__ . '/files/migrations';
        if (is_dir($dir)) { system("rm -rf $dir"); }

    }

}


