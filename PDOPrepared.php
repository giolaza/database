<?php
/**
 * PDOPrepared.php
 *
 * @category   DB
 * @package    GL
 * @author     Giorgi Lazashvili <giolaza@gmail.com>
 * @version    2 (26 MAY 2018)
 *
 *
 *
 * (array) PDOPrepared->execute(array) - pdo execute, required  SELECT ... to use, because if is called for UPDATE will return pdo exception while fetching result
 * RESULT -
 * $result[0]['key1'],$result[0]['key2'],$result[0]['key3']...
 * $result[1]['key1'],$result[1]['key2'],$result[1]['key3']...
 * ...
 * (array) PDOPrepared->execute_one(array) - pdo execute, required SELECT ... LIMIT 1 to use,
 * because if is called for UPDATE will return pdo exception while fetching result
 * RESULT -  $result['key1'],$result['key2'],$result['key3']...
 * (boolean) PDOPrepared->execute_only(array) -    pdo execute, recommended for UPDATE, DELETE, etc...
 * NOTE -    in pdo if update function affects 0 rows it returns FALSE,
 * BUT in this class return TRUE if there was not errors during execute
 ****************************************************************************************************
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
     * @return PDOPrepared|void|null
     * @throws \Exception
     */
    public function prepare($query)
    {

        try {
            $this->prepared = $this->connect->prepare($query);
        } catch (\Exception $e) {
            $this->logErr('PDO prepare catch:<br>' . PHP_EOL . '--query: ' . $query . '<br>' . PHP_EOL . '--message: ' . $e->getMessage());

            return;
        }

        if (!$this->prepared) {
            $this->logErr('PDO prepare not ready:<br>' . PHP_EOL . '--query: ' . $query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), 1));
        }

    }

    /**
     * @param null $array
     * @return array|null
     * @throws \Exception
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
     * @param null $array
     * @return array|mixed
     */
    public function execute_one($array = null)
    {
        $result = $this->execute($array);

        if (isset($result[0])) return $result[0];
        else return array();
    }

    /**
     * @param null $array
     * @return bool
     * @throws \Exception
     */
    public function execute_only($array = null)
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
