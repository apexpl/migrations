<?php
declare(strict_types = 1);

namespace Apex\Migrations\Cli;

use Apex\Migrations\{Config, Migrations};
use Apex\Migrations\Handlers\HistoryLoader;
use Apex\Migrations\Cli\Cli;
use Apex\Container\Di;


/**
 * History CLI command
 */
class History
{

    /**
     * Constructor
     */
    public function __construct(
        private Config $config, 
        private Migrations $migrations, 
        private HistoryLoader $loader, 
    ) { 

    }

    /**
     * Process
     */
    public function process():void
    {

        // Get options
        list($args, $opt) = Cli::getArgs(['package', 'txid', 'start', 'limit', 'sort']);
        $package = $opt['package'] ?? '';
        $txid = $opt['txid'] ?? '';
        $start = $opt['start'] ?? 0;
        $limit = $opt['limit'] ?? 0;
        $sort_desc = isset($opt['sort']) && strtolower($opt['sort']) == 'asc' ? false : true;

        // View package
        if ($package != '') { 
            $this->viewPackage($package, (int) $limit, (int) $start, $sort_desc);
            return;

        // View transaction
        } elseif ($txid != '') { 
            $this->viewTransaction((int) $txid);
            return;
        }

        // List transactions
        $txs = $this->loader->listTransactions((int) $limit, (int) $start, $sort_desc);
        if (count($txs) == 0) { 
            Cli::send("\r\nThere is no transaction history.  Nothing to show.\r\n");
            return;
        }
        Cli::sendHeader("Transactions");

        // Go through transactions
        foreach ($txs as $txid => $row) { 
            $date = date('D, d M Y H:i:s', $row['installed_at']->getTimestamp());
            Cli::send("TxID $txid -- Installed $row[total] migrations in " . $this->migrations->formatSecs($row['ms']) . " [$date]\r\n");
        }

        // Send footer
        Cli::send("\r\nYou may view details on any transaction with:\r\n      apex-migrations history --txid XX\r\n\r\n");
        Cli::send("You may rollback your database up to and including any transaction with:\r\n      apex-migrations rollback --txid XX\r\n\r\n");
    }

    /**
     * View transaction
     */
    private function viewTransaction(int $txid):void
    {

        // Get transaction
        if (!$installs = $this->loader->getTransaction($txid)) { 
            Cli::send("\r\nThe txid $txid does not exist.  Nothing to show.\r\n\r\n");
            return;
        }
        Cli::sendHeader("Transaction ID: $txid");

        // Go through installed
        list($total, $total_ms) = [0, 0];
        foreach ($installs as $row) {
            Cli::send("Package: $row[package] -- installed $row[class_name] in " . $this->migrations->formatSecs($row['ms']) . "\r\n");
            $total++;
            $total_ms += $row['ms'];
        }

        // SEnd footer
        Cli::send("\r\nTotal Installs: $total in " . $this->migrations->formatSecs($total_ms) . "\r\n\r\n");
    }

    /**
     * View package
     */
    private function viewPackage(string $package, int $limit = 0, int $start = 0, bool $sort_desc = true):void
    {

        // Get installed
        if (!$installed = $this->loader->getPackage($package, $limit, $start, $sort_desc)) { 
            Cli::send("\r\nThe package '$package' does not exist or has never had any migrations installed on it.  Nothing to show.\r\n\r\n");
            return;
        }
        Cli::sendHeader("Package: $package");

        // GO through installed
        list($total, $total_ms) = [0, 0];
        foreach ($installed as $row) { 
            Cli::send("TxID $row[txid] -- installed $row[class_name] in " . $this->migrations->formatSecs($row['ms']) . "\r\n");
            $total++;
            $total_ms += $row['ms'];
        }

        // Send footer
        Cli::send("\r\nTotal $total installed in " . $this->migrations->formatSecs($total_ms) . "\r\n\r\n");
    }

}


