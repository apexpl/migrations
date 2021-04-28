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

        // Format dir names in packages
        foreach ($this->packages as $alias => $vars) { 
            if (str_starts_with($vars['dir'], '/')) { 
                continue;
            }
            $yaml_dir = dirname(realpath($file));
            $this->packages[$alias]['dir'] = realpath($yaml_dir . '/' . $vars['dir']);
        }

    }

    /**
     * Get table name
     */
    public function getTableName():string
    {
        return $this->table_name;
    }

    /**
     * Set table name
     */
    public function setTableName(string $name):void
    {
        $this->table_name = $name;
    }

    /**
     * Get author
     */
    public function getAuthor():array
    {
        return $this->author;
    }

    /**
     * Set author username
     */
    public function setAuthorUsername(string $username):void
    {
        $this->author['username'] = $username;
    }

    /**
     * Set author name
     */
    public function setAuthorName(string $name):void
    {
        $this->author['full_name'] = $name;
    }

    /**
     * Set author e-mail
     */
    public function setAuthorEmail(string $email):void
    {
        $this->author['email'] = $email;
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

    /**
     * Set packages
     */
    public function addPackage(string $alias, string $dir, string $namespace):void
    {
        $this->packages[$alias] = [
            'dir' => $dir, 
            'namespace' => $namespace
        ];
    }

    /**
     * Delete package
     */
    public function deletePackage(string $alias):void
    {
        unset($this->packages[$alias]);
    }

    /**
     * Purge packages
     */
    public function purgePackages():void
    {
        $this->packages = [];
    }

}



