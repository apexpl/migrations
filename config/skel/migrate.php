<?php
declare(strict_types = 1);

namespace ~namespace~\~class_name~;

use Apex\Migrations\Handlers\Migration;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;

/**
 * Migration - ~class_name~
 */
class migrate extends Migration
{

    // Properties
    public string $author_username = '~author_username~';
    public string $author_name = '~author_name~';
    public string $author_email = '~author_email~';
    public string $branch = '~branch~';


    /**
     * Install
     */
    public function install(DbInterface $db):void
    {

        // Execute install.sql file
        $db->executeSqlFile(__DIR__ .'/install.sql');
    }

    /**
     * Rollback
     */
    public function rollback(DbInterface $db):void
    {

        // Execute SQL file
        $db->executeSqlFile(__DIR__ . '/rollback.sql');
    }

}


