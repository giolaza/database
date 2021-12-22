<?php
/**
 * PDOPrepared.php
 *
 * @category   Database
 * @package    GioLaza
 * @author     Giorgi Lazashvili <giolaza@gmail.com>
 *
 */

namespace GioLaza\Database;


class PDOPrepared extends sqlDB
{
    /**
     * @var null
     */
    public $prepared = null;

    /**
     * @var null
     */
    public $result = null;

    /**
     * @param $query
     * @return void
     */
    public function prepare($query)
    {
        $this->query = $query;
        try {
            $this->prepared = $this->connect->prepare($this->query);
        } catch (\Exception $e) {
            $this->logErr('PDO prepare catch:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . $e->getMessage());

            return;
        }

        if (!$this->prepared) {
            $this->logErr('PDO prepare not ready:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), 1));
        }

    }

    /**
     * @param $array
     * @return array|null
     */
    public function execute($array = null)
    {
        $this->result = false;
        if ($this->prepared == null) {
            $this->logErr('execute: prepared is null');
            return null;
        }

        try {
            $this->result = $this->prepared->execute($array);
        } catch (\Exception $e) {
            $catchInfo = $e->getMessage() . '<br>' . PHP_EOL . 'in ' . $e->getFile() . '<br>' . PHP_EOL . 'on line ' . $e->getLine();
        }

        if ($this->result) {
            $result = $this->do_fetch($this->prepared);
        } else {
            $result = array();

            $errInfo = PHP_EOL . '<br>' . PHP_EOL;
            for ($i = 0; $i < 20; $i++) $errInfo .= '-';
            $errInfo .= PHP_EOL;
            $errInfo .= '<br>--catch Info: ';
            $errInfo .= PHP_EOL;
            $errInfo .= $catchInfo;

            $errInfo .= PHP_EOL . '<br>' . PHP_EOL;
            for ($i = 0; $i < 20; $i++) $errInfo .= '-';
            $errInfo .= PHP_EOL;
            $errInfo .= '<br>--prepare err message: ';
            $errInfo .= PHP_EOL;
            $errInfo .= print_r($this->prepared->errorInfo(), true);

            $errInfo .= PHP_EOL . '<br>' . PHP_EOL;
            for ($i = 0; $i < 20; $i++) $errInfo .= '-';
            $errInfo .= PHP_EOL . '<br>' . PHP_EOL;
            $errInfo .= '<br>--prepare array: ';
            $errInfo .= PHP_EOL;
            $errInfo .= print_r($array, true);

            $errInfo .= PHP_EOL . '<br>' . PHP_EOL;
            for ($i = 0; $i < 20; $i++) $errInfo .= '-';
            $errInfo .= PHP_EOL;
            $errInfo .= '<br>--prepare debug: ';
            $errInfo .= PHP_EOL;

            ob_start();
            $this->prepared->debugDumpParams();
            $errInfo .= ob_get_contents();
            ob_end_clean();

            $this->logErr('execute: ' . $errInfo);
        }

        return $result;
    }

    /**
     * @param $array
     * @return array|mixed
     */
    public function execute_one($array = null)
    {
        $result = $this->execute($array);

        if (isset($result[0])) return $result[0];
        else return array();
    }

    /**
     * @param $array
     * @return bool
     */
    public function execute_only($array = null): bool
    {
        $this->result = false;
        if ($this->prepared == null) {
            $this->logErr('execute: prepared is null');
            return false;
        }

        try {
            $this->result = $this->prepared->execute($array);
        } catch (\Exception $e) {
            $catchInfo = $e->getMessage() . PHP_EOL . 'in ' . $e->getFile() . PHP_EOL . 'on line ' . $e->getLine();
        }

        if ($this->result) {
            return true;
        } else {
            $errInfo = PHP_EOL . '<br>' . PHP_EOL;
            for ($i = 0; $i < 20; $i++) $errInfo .= '-';
            $errInfo .= PHP_EOL;
            $errInfo .= '<br>--catch Info: ';
            $errInfo .= PHP_EOL;
            $errInfo .= $catchInfo;

            $errInfo .= PHP_EOL . '<br>' . PHP_EOL;
            for ($i = 0; $i < 20; $i++) $errInfo .= '-';
            $errInfo .= PHP_EOL;
            $errInfo .= '<br>--prepare err message: ';
            $errInfo .= PHP_EOL;
            $errInfo .= print_r($this->prepared->errorInfo(), true);

            $errInfo .= PHP_EOL . '<br>' . PHP_EOL;
            for ($i = 0; $i < 20; $i++) $errInfo .= '-';
            $errInfo .= PHP_EOL;
            $errInfo .= '<br>--prepare array: ';
            $errInfo .= PHP_EOL;
            $errInfo .= print_r($array, true);

            $errInfo .= PHP_EOL . '<br>' . PHP_EOL;
            for ($i = 0; $i < 20; $i++) $errInfo .= '-';
            $errInfo .= PHP_EOL;
            $errInfo .= '<br>--prepare debug: ';
            $errInfo .= PHP_EOL;

            ob_start();
            $this->prepared->debugDumpParams();
            $errInfo .= ob_get_contents();
            ob_end_clean();

            $this->logErr('execute_only: ' . $errInfo);
            return false;
        }
    }
}
