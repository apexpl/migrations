<?php
declare(strict_types = 1);

namespace Apex\Migrations\Handlers;

use Apex\Migrations\Config;
use Apex\Db\Interfaces\DbInterface;


/**
 * History loader
 */
class HistoryLoader
{

    /**
     * Constructor
     */
    public function __construct(
        private DbInterface $db, 
        private Config $config
    ) { 

    }

    /**
     * List transactions
     */
    public function listTransactions(int $limit = 0, int $start = 0, bool $sort_desc = true):array
    {

        // Initialize
        $order_dir = $sort_desc === true ? 'DESC' : 'ASC';
        $table_name = $this->config->getTableName();

        // Get SQL
        $sql = "SELECT transaction_id, count(*) total, sum(execute_ms) ms FROM $table_name GROUP BY transaction_id ORDER BY transaction_id $order_dir";
        if ($limit > 0) { 
            $sql .= ' LIMIT ' . $start . ',' . $limit;
        }

        // Go through rows
        $txs = [];
        $rows = $this->db->query($sql);
        foreach ($rows as $row) { 

            // Get installed at
            $installed_at = $this->db->getField("SELECT installed_at FROM $table_name WHERE transaction_id = %i ORDER BY installed_at LIMIT 1", $row['transaction_id']);
            $installed_at = new \DateTime($installed_at);

            // Add to results
            $trans_id = (string) $row['transaction_id'];
            $txs[$trans_id] = [
                'total' => $row['total'], 
                'ms' => (int) $row['ms'], 
                'installed_at' => $installed_at
            ];
        }

        // Return
        return $txs;
    }

    /**
     * Get transaction
     */
    public function getTransaction(int $txid):?array
    {

        // Init
        $table_name = $this->config->getTableName();

        // Get installed at
        if (!$installed_at = $this->db->getField("SELECT installed_at FROM $table_name WHERE transaction_id = %i ORDER BY installed_at LIMIT 1", $txid)) { 
            return null;
        }

        // Go through installs
        $installs = [];
        $rows = $this->db->query("SELECT * FROM $table_name WHERE transaction_id = %i ORDER BY package,id", $txid);
        foreach ($rows as $row) { 

            $installs[] = [
                'package' => $row['package'], 
                'class_name' => $row['class_name'], 
                'ms' => (int) $row['execute_ms'], 
                'installed_at' => new \DateTime($row['installed_at'])
            ];
        }

        // Return
        return $installs;
    }

    /**
     * Get package
     */
    public function getPackage(string $package, int $limit = 0, int $start = 0, bool $sort_desc = true):?array
    {

        // Initialize
        $table_name = $this->config->getTableName();
        $order_dir = $sort_desc === true ? 'DESC' : 'ASC';

        // Set SQL
        $sql = "SELECT * FROM $table_name WHERE package = %s ORDER BY installed_at $order_dir";
        if ($limit > 0) { 
            $sql .= ' LIMIT ' . $start . ',' . $limit;
        }

        // Go through rows
        $installs = [];
        $rows = $this->db->query($sql, $package);
        foreach ($rows as $row) { 

            // Add to installs
            $installs[] = [
                'txid' => $row['transaction_id'], 
                'ms' => (int) $row['execute_ms'], 
                'class_name' => $row['class_name'], 
                'installed_at' => new \DateTime($row['installed_at'])
            ];
        }

        // Return
        return count($installs) > 0 ? $installs : null;
    }

}



