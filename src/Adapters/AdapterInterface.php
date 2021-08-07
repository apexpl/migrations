<?php
declare(strict_types = 1);

namespace Apex\Migrations\Adapters;

/**
 * Adapter interface
 */
interface AdapterInterface
{

    /**
     * Create
     */
    public function create(string $dir_name, string $namespace, string $alias, string $branch, array $author = []):string;

    /**
     * Install migration
     */
    public function install(string $class_name, string $dirname, string $namespace, array $entity_paths = []):int;

    /**
     * Rollback
     */
    public function rollback(string $class_name, string $namespace, string $dirname, array $entity_paths = []):void;



}

