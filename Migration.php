#!/usr/bin/php
<?php

class Migration
{
    public $connection;
    public $dbHost = "localhost";
    public $dbUser = "root";
    public $dbPass = "";

    public $dbNameGames = "games";
    public $dbNameOperators = "operators";
    public $fileGamesCSV = "games.csv";
    public $fileOperatorsCSV = "operators.csv";
    public $fileProvidersCSV = "providers.csv";
    public $affiliateId = 6;
    public $marketId = 9;

    public $dbName = "wordpress_db";
    public $fileCSV = "pros.csv";
    public $tablePrefix = "wp_";

    /**
     * Migration constructor.
     * get the args from CLI
     * e.g. $ php Migration.php games
     */
    public function __construct()
    {
        $call = null;
        if ( ! empty($_SERVER['argv'])) {
            $call = ! empty($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;
        } elseif ( ! empty($argv)) {
            $call = ! empty($argv[1]) ? $argv['argv'][1] : null;
        }

        if ( ! empty($call)) {
            $this->$call();
        } else {
            $this->games();
            $this->operators();
            $this->providers();
            //			$this->export();
        }
    }

    /**
     * Start DB connection
     */
    private function connect($dbName)
    {
        $this->connection = mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, $dbName);
        if ( ! $this->connection) {
            die('ERROR: DB not connected.');
        }
        echo "DB connected." . "\n";
    }

    /**
     * Close DB connection
     */
    private function close()
    {
        mysqli_close($this->connection);
    }

    public function games()
    {
        $this->connect($this->dbNameGames);
        $sql = "SELECT g.name, g.short_name FROM games g LEFT JOIN affiliate_games ag ON g.id = ag.game_id WHERE ag.affiliate_id = {$this->affiliateId} AND ag.market_id = {$this->marketId}";
        if ($result = mysqli_query($this->connection, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                echo "Migrating games ...\n";
                $header = ['Name', 'Short Name'];
                $this->writeCSV($result, $this->fileGamesCSV, $header);
                // Free result set
                mysqli_free_result($result);
            } else {
                echo "No records matching your query were found.\n";
            }
        } else {
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->connection) . "\n";
        }
        $this->close();
    }

    public function operators()
    {
        $this->connect($this->dbNameOperators);
        $sql = "SELECT o.name, o.short_name FROM operators o LEFT JOIN affiliate_operators ao ON o.id = ao.operator_id WHERE ao.affiliate_id = {$this->affiliateId} AND ao.market_id = {$this->marketId}";
        if ($result = mysqli_query($this->connection, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                echo "Migrating operators ...\n";
                $header = ['Name', 'Short Name'];
                $this->writeCSV($result, $this->fileOperatorsCSV, $header);
                // Free result set
                mysqli_free_result($result);
            } else {
                echo "No records matching your query were found.\n";
            }
        } else {
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->connection) . "\n";
        }
        $this->close();
    }

    public function providers()
    {
        $this->connect($this->dbNameGames);
        $sql = "SELECT DISTINCT g.provider FROM games g LEFT JOIN affiliate_games ag ON g.id = ag.game_id WHERE ag.affiliate_id = {$this->affiliateId} AND ag.market_id = {$this->marketId}";
        if ($result = mysqli_query($this->connection, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                echo "Migrating software providers ...\n";
                $this->writeCSV($result, $this->fileProvidersCSV);
                // Free result set
                mysqli_free_result($result);
            } else {
                echo "No records matching your query were found.\n";
            }
        } else {
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->connection) . "\n";
        }
        $this->close();
    }

    /**
     * Export WP Advanced Custom Fields
     */
    public function export()
    {
        $this->connect($this->dbName);
        $postmeta = $this->tablePrefix . "postmeta";
        $post     = $this->tablePrefix . "posts";
        $sql      = "SELECT p.post_name, m.meta_value FROM {$postmeta} m LEFT JOIN {$post} p ON m.post_id = p.id WHERE m.meta_key LIKE '%r_casino_pros' AND m.meta_value NOT LIKE 'field_%' AND p.post_status = 'publish'";
        if ($result = mysqli_query($this->connection, $sql)) {
            if (mysqli_num_rows($result) > 0) {
                echo "Migrating pro's ...\n";
                $header = ['Short code', 'Pro_1', 'Pro_2', 'Pro_3'];
                $this->groupedWriteCSV($result, $this->fileCSV, $header);
                // Free result set
                mysqli_free_result($result);
            } else {
                echo "No records matching your query were found.\n";
            }
        } else {
            echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->connection) . "\n";
        }
        $this->close();
    }

    private function writeCSV($result, $fileName, $header = null)
    {
        $fp = fopen($fileName, 'w');
        // If there is an header
        if ( ! empty($header)) {
            fputcsv($fp, $header);
        }
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            fputcsv($fp, $row);
        }

        fclose($fp);
    }

    private function groupedWriteCSV($result, $fileName, $header = null)
    {
        $fp = fopen($fileName, 'w');
        // If there is an header
        if ( ! empty($header)) {
            fputcsv($fp, $header);
        }

        $grouped = [];
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $grouped[$row['post_name']][] = $row['meta_value'];
        }

        foreach ($grouped as $name => $row) {
            $line = ['name' => $name];
            foreach ($row as $key => $value) {
                if ($value) {
                    $line['pro_' . ($key + 1)] = $value;
                }
            }
            fputcsv($fp, $line);
        }

        fclose($fp);
    }

    /**
     * To convert DB charset (Optional)
     *
     * @param $string
     *
     * @return string
     */
    private function convertEncoding($string)
    {
        return mb_convert_encoding($string, 'UTF-8',
            mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
    }
}

$migration = new Migration();