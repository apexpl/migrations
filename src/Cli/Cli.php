<?php
declare(strict_types = 1);

namespace Apex\Migrations\Cli;

use Apex\Migrations\Cli\Commands;
use Apex\Container\Di;

/**
 * Handles all CLI functionality for for migrations
 */
class Cli
{

    // Properties
    public static array $args = [];
    public static array $options = [];

    /**
     * Run CLI command
     */
    public static function run()
    {

        // Get arguments
        list($args, $opt) = self::getArgs(['dbname', 'user', 'password', 'host', 'port']);
        $method = array_shift($args) ?? '';
        $method = str_replace('-', '_', $method);

        // Check for command
        $class_name = "Apex\\Migrations\\Cli\\" . ucwords($method);
        if (!class_exists($class_name)) { 
            self::showHelp();
        } else { 
            $cmd = Di::make($class_name);
            $cmd->process();
        }

    }

    /**
     * Show help
     */
    public static function showHelp():void
    {

        // Send header
        self::sendHeader('Apex Migrations');

        // Set vars
        $cmds = [
            'create' => 'Create new migration', 
            'status' => 'View pending migrations', 
            'history' => 'View previously installed migrations', 
            'migrate' => 'Install pending migrations', 
            'rollback' => 'Rollback previously installed migrations', 
            'help' => 'Use help <COMMAND> for details on any command'
        ];

        // Go through commands
        foreach ($cmds as $cmd => $desc) { 
            $line = str_pad($cmd, 20, ' ', STR_PAD_RIGHT) . $desc;
            Cli::send("$line\r\n");
        }

        // Exit
        exit(0);
    }

    /**
     * Get command line arguments and options
     */
    public static function getArgs(array $has_value = []):array
    {

        // Initialize
        global $argv;
        list($args, $options, $tmp_args) = [[], [], $argv];
        array_shift($tmp_args);

        // Go through args
        while (count($tmp_args) > 0) { 
            $var = array_shift($tmp_args);

            // Long option with =
            if (preg_match("/^--(\w+?)=(.+)$/", $var, $match)) { 
                $options[$match[1]] = $match[2];

            } elseif (preg_match("/^--(.+)$/", $var, $match) && in_array($match[1], $has_value)) { 


                $value = isset($tmp_args[0]) ? array_shift($tmp_args) : '';
                if ($value == '=') { 
                    $value = isset($tmp_args[0]) ? array_shift($tmp_args) : '';
                }
                $options[$match[1]] = $value;

            } elseif (preg_match("/^--(.+)/", $var, $match)) { 
                $options[$match[1]] = true;

            } elseif (preg_match("/^-(\w+)/", $var, $match)) { 
                $chars = str_split($match[1]);
                foreach ($chars as $char) { 
                    $options[$char] = true;
                }

            } else { 
                $args[] = $var;
            }
        }

        // Set properties
        self::$args = $args;
        self::$options = $options;

        // Return
        return array($args, $options);
    }

    /**
     * Get input from the user.
     */
    public static function getInput(string $label, string $default_value = ''):string
    { 

        // Echo label
        self::send($label);

        // Get input
        $value = strtolower(trim(fgets(STDIN)));
        if ($value == '') { $value = $default_value; }

        // Check quit / exist
        if (in_array($value, ['q', 'quit', 'exit'])) { 
            self::send("Ok, goodbye.\n\n");
            exit(0);
        }

        // Return
        return $value;
    }

    /**
     * Send output to user.
     */
    public static function send(string $data):void
    {

        if (!defined('STDOUT')) {
            echo $data;
        } else {
            fputs(STDOUT, $data);
        }

    }

    /**
     * Send header to user
     */
    public static function sendHeader(string $label):void
    {
        self::send("------------------------------\n");
        self::send("-- $label\n");
        self::send("------------------------------\n\n");
    }

}

