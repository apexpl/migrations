<?php

/**
 * Container definitions for Migrations
 */

use Apex\Db\Interfaces\DbInterface;
use Apex\Debugger\Interfaces\DebuggerInterface;

return [

    /**
     * Database.  Change with your credentials.
     */
    DbInterface::class => [\Apex\Db\Drivers\mySQL\mySQL::class, ['params' => [
        'dbname' => 'apex', 
        'user' => 'boxer', 
        'password' => 'white4882']]
    ], 

    /**
     * Location of YAML config file.
     */
    'migrations.yaml_file' => __DIR__ . '/migrations.yml'

];


