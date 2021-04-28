<?php
declare(strict_types = 1);

namespace Apex\Migrations;

use Apex\Container\Di;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Apex\Migrations\Exceptions\MigrationsYamlConfigException;


/**
 * Migrations config
 */
class Config
{

    // Properties
    private string $table_name;
    private array $author;
    private array $packages;

    /**
     * Constructor
     */
    public function __construct(
        private string $yaml_file = ''
    ) { 

        // Load file
        $this->loadFile($yaml_file);

    }

    /**
     * Load file
     */
    private function loadFile(string $file):void
    {

        // Get YAML file from DI container, if blank
        if ($file == '') { 
            $file = Di::get('migrations.yaml_file');
        }

        // Load YAML file
        try {
            $vars = Yaml::parseFile($file);
        } catch (ParseException $e) { 
            throw new MigrationsYamlConfigException("Unable to parse YAML file at $file.  Error: " . $e->getMessage());
        }

        // Set variables
        $this->table_name = $vars['table_name'] ?? 'internal_migrations';
        $this->author = $vars['author'] ?? [];
        $this->packages = $vars['packages'] ?? [];
    }

    /**
     * Get table name
     */
    public function getTableName():string
    {
        return $this->table_name;
    }

    /**
     * Get author
     */
    public function getAuthor():array
    {
        return $this->author;
    }

    /**
     * Get packages
     */
    public function getPackages():array
    {
        return $this->packages;
    }

    /**
     * Get single package
     */
    public function getPackage(string $alias = 'default'):?array
    {

        // Check if now exists
        if (!isset($this->packages[$alias])) { 
            return null;
        }

        // Get info
        return [rtrim($this->packages[$alias]['dir'], '/'), $this->packages[$alias]['namespace']];
    }

}



