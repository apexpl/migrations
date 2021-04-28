<?php
declare(strict_types = 1);

namespace Apex\Migrations\Cli;

use Apex\Migrations\Config;
use Apex\Migrations\Handlers\{ClassManager, Remover};
use Apex\Migrations\Cli\Cli;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;


/**
 * Migrate CLI command
 */
class Rollback
{

    /**
     * Constructor
     */
    public function __construct(
        private Config $config, 
        private ClassManager $manager, 
        private DbInterface $db 
    ) { 

    }

    /**
     * Process
     */
    public function process():void
    {

        // Get options
        if (!list($txid, $package, $last, $all) = $this->getOptions()) { 
            return;
        }
        $remover = Di::make(Remover::class, ['send_output' => true]);

        // Transaction
        if ($txid > 0) { 
            $remover->rollbackTransaction((int) $txid);
        } elseif ($package != '' && $all === true) { 
            $remover->removePackage($package);
        } elseif ($package != '' && $last > 0) { 
            $remover->rollbackLastPackage($package, $last);
        } elseif ($last > 0) { 
            $remover->rollbackLastTransaction($last);
        } else { 
            Cli::send("\r\nInvalid options.  Please run 'help rollback' for details on this function.  Northing to do.\r\n\r\n");
            return;
        }

        // Send response
        Cli::send("\r\nSuccessfully rollback database as specified.  Database up to date.\r\n\r\n");
    }

    /**
     * Get options
     */
    private function getOptions():array
    {

        // Get options
        list($args, $opt) = Cli::getArgs(['package','txid','last','all']);
        $package = $opt['package'] ?? '';
        $txid = (int) ($opt['txid'] ?? 0);
        $last = (int) ($opt['last'] ?? 0);
        $all = isset($opt['all']) ? true : false;

        // Check package, if needed
        if ($package != '' && !$pkg = $this->config->getPackage($package)) { 
            Cli::send("\r\nThe package '$package' either does not exist or no migrations have been installed against it.  Nothing to do.\r\n\r\rn");
            return null;
        }

        // Check transaction
        $table_name = $this->config->getTableName();
        if ($txid != '' && !$row = $this->db->query("SELECT * FROM $table_name WHERE transaction_id = %i", $txid)) { 
            Cli::send("\r\nThe transaction id $txid does not exist.  Nothing to do.\rn\r\n");
            return null;
        }

        // Return
        return [$txid, $package, $last, $all];
    }

}



