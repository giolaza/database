<?php

namespace GioLaza\Database;

use Exception;
use PDOStatement;

/**
 * PDOPrepared - A class for using prepared statements with PDO
 *
 * @category   Database
 * @package    GioLaza
 * @author     Giorgi Lazashvili <giolaza@gmail.com>
 */
class PDOPrepared extends SqlDB
{
    /**
     * @var null The prepared statement
     */
    public $prepared = null;

    /**
     * @var null The query result
     */
    public $result = null;

    /**
     * Prepare a query for execution
     *
     * @param string $query The query to prepare
     *
     * @return void
     */

    /**
     * Prepare a query for execution
     *
     * @param string $query The query to prepare
     *
     */
    public function prepare(string $query)
    {
        $this->query = $query;

        try {
            $this->prepared = $this->connect->prepare($this->query);

            return $this->prepared;
        } catch (Exception $e) {
            $this->logErr('PDO prepare catch:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array|null $array
     * @return bool
     */
    public function execute_only(?array $array = null): bool
    {
        return $this->executeOnly($array);
    }

    /**
     * Execute a prepared statement and return a boolean indicating success or failure
     *
     * @param array|null $array An array of values to substitute into the prepared statement
     *
     * @return bool Whether the prepared statement executed successfully
     */
    public function executeOnly(?array $array = null): bool
    {
        if (!$this->prepared instanceof PDOStatement) {
            $this->logErr('executeOnly: prepared is null');
            return false;
        }

        try {
            $this->result = $this->prepared->execute($array);
        } catch (Exception $e) {
            $this->proceedPreparedError('executeOnly', $e->getMessage() . PHP_EOL . 'in ' . $e->getFile() . PHP_EOL . 'on line ' . $e->getLine(), $array);
            return false;
        }

        return true;
    }

    /**
     * Execute a prepared statement
     *
     * @param array|null $array An array of values to substitute into the prepared statement
     *
     * @return array|null The query result
     */
    public function execute(?array $array = null): ?array
    {
        $this->result = false;

        if (!$this->prepared instanceof PDOStatement) {
            $this->logErr('execute: prepared is null');
            return null;
        }

        try {
            $this->result = $this->prepared->execute($array);
        } catch (Exception $e) {
            $catchInfo = $e->getMessage() . '<br>' . PHP_EOL . 'in ' . $e->getFile() . '<br>' . PHP_EOL . 'on line ' . $e->getLine();
            $this->proceedPreparedError('execute', $catchInfo, $array);
            return [];
        }

        if ($this->result) {
            return $this->doFetch($this->prepared);
        }
        else {
            $this->proceedPreparedError('execute', '', $array);
            return [];
        }
    }

    protected function proceedPreparedError(string $method, string $catchInfo, ?array $executedParams = null)
    {
        $separator = str_repeat('-', 20);
        $errorInfo = "{$separator}<br>--catch Info: " . PHP_EOL . $catchInfo . PHP_EOL;
        $errorInfo .= "{$separator}<br>--prepare err message: " . PHP_EOL . print_r($this->prepared->errorInfo(), true) . PHP_EOL;
        $errorInfo .= "{$separator}<br>--prepare array: " . PHP_EOL . print_r($executedParams, true) . PHP_EOL;
        $errorInfo .= "{$separator}<br>--prepare debug: " . PHP_EOL;
        ob_start();
        $this->prepared->debugDumpParams();
        $errorInfo .= ob_get_clean();
        $this->logErr($method . ': ' . $errorInfo);
    }

    /**
     * @param array|null $array
     * @return array
     */
    public function execute_one(?array $array = null): array
    {
        return $this->executeOne($array);
    }

    /**
     * Execute a prepared statement and return the first row of the result
     *
     * @param array|null $array An array of values to substitute into the prepared statement
     *
     * @return array The first row of the query result
     */
    public function executeOne(?array $array = null): array
    {
        $result = $this->execute($array);

        return $result[0] ?? [];
    }
}
