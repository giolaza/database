<?php
/**
 * SqlDB.php
 *
 * @category   Database
 * @package    GioLaza
 * @author     Giorgi Lazashvili <giolaza@gmail.com>
 *
 */

namespace GioLaza\Database;

use GioLaza\Database\PDOPrepared as PDOPrepared;
use GioLaza\Logger\Log as Log;
use \Exception;
use PDO;

/**
 * Class SqlDB
 * @package GioLaza\Database
 */
class SqlDB
{
    /**
     * Limit of insert values in same time
     */
    const INSERT_LIMIT = 51;

    /**
     * DB driver
     */
    const DB_Driver = 'PDO';

    /**
     * Log file name
     */
    const LOG_FILE = 'engine.DBErrors.log';

    /**
     * @var null|db connection
     */
    public $connect = null;

    /**
     * @var null|string
     */
    protected $query = null;


    //public $resultCount=0;

    public function __construct()
    {
        if (!defined('GIOLAZA_SHOW_ERRORS')) {
            define('GIOLAZA_SHOW_ERRORS', false, 1);
        }

        if (!defined('GIOLAZA_SAVE_ERRORS')) {
            define('GIOLAZA_SAVE_ERRORS', true, 1);
        }
    }

    /** CONNECTION METHODS */

    /**
     * @param $sql_host
     * @param $sql_user
     * @param $sql_pass
     * @param $sql_db_name
     * @return bool
     * @throws Exception
     */
    public function connect($sql_host, $sql_user, $sql_pass, $sql_db_name)
    {
        return $this->db_open($sql_host, $sql_user, $sql_pass, $sql_db_name);
    }

    /**
     * @param $sql_host
     * @param $sql_user
     * @param $sql_pass
     * @param $sql_db_name
     * @return bool
     * @throws Exception
     */
    public function db_open($sql_host, $sql_user, $sql_pass, $sql_db_name)
    {
        if (class_exists(self::DB_Driver)) {
            try {
                if (self::DB_Driver === 'PDO') {
                    $this->connect = new PDO('mysql:host=' . $sql_host . ';dbname=' . $sql_db_name . ';charset=UTF8', $sql_user, $sql_pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT => false));
                } else {
                    $this->logErr('db_open check driver: connection type not supported by engine');
                    $this->connect = null;
                    return false;
                }
            } catch (Exception $e) {
                $this->logErr('db_open catch: ' . $e->getMessage());
                $this->connect = null;
                return false;
            }
        } else {
            $this->logErr('db_open check driver: class not found');
            return false;
        }


        //if detected error
        if (isset($this->connect->connect_errno) && $this->connect->connect_errno) {
            $this->logErr('db_open: ' . $this->connect->connect_error);
            $this->connect = null;
            return false;
        } else {
            //else check connection
            if (!$this->connect) {
                $this->logErr('db_open: false connection');
                return false;
            } else {
                //if all ok
                //$this->connect->setAttribute(\PDO::ATTR_EMULATE_PREPARES,false);
                return true;
            }
        }
    }

    /**
     *
     */
    public function db_close()
    {
        if ($this->connect != null) {
            $this->connect = null;
        } else {
            $this->logErr('db_close: connect is null');
        }
    }

    /**
     * @return bool
     */
    public function check_connection()
    {
        if (self::DB_Driver === 'PDO') {
            if ($this->connect == null) return false;
            else return true;
        } else return false;

    }
    /*** CONNECTION METHODS */


    /** QUERY FUNCTIONS */

    /**
     * @param string $query
     * @return bool
     * @throws Exception
     */
    public function do_only($query = '')
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return false;
        }


        try {
            $result = $this->connect->query($this->query);
        } catch (Exception $e) {
            $this->logErr('do_only catch:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . $e->getMessage());

            return false;
        }


        if ($result) {
            return true;
        } else {
            $this->logErr('do_only:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), true));
            return false;
        }
    }

    /**
     * @param string $query
     * @return array
     * @throws Exception
     */
    public function do_one($query = '')
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return array();
        }


        try {
            $result = $this->connect->query($this->query);
        } catch (Exception $e) {
            $this->logErr('do_one catch:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . $e->getMessage());

            return array();
        }

        if (!$result) {
            $this->logErr('do_one:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), true));
            return array();
        } else {
            $res = $this->do_fetch($result, true);
            if (isset($res) && is_array($res)) return $res;
            else return array();
        }
    }

    /**
     * @param string $query
     * @return array
     * @throws Exception
     */
    public function do_all($query = '')
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return array();
        }


        try {
            $result = $this->connect->query($this->query);
        } catch (Exception $e) {
            $this->logErr('do_all catch:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . $e->getMessage());

            return array();
        }

        if (!$result) {
            $this->logErr('do_all:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), true));

            return array();
        } else {
            $res = $this->do_fetch($result);
            if ($res) return $res;
            else return array();
        }
    }

    /**
     * @param string $query
     * @param string $key
     * @return array
     * @throws Exception
     */
    public function do_allById($query = '', $key = 'id')
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return array();
        }

        $result = [];

        $raw = $this->do_all($query);

        foreach ($raw as $row) {
            $id = $row[$key];
            $result[$id] = $row;
        }

        return $result;
    }

    /**
     * @param string $query
     * @return bool
     * @throws Exception
     */
    public function do_multi($query = '')
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return false;
        }

        $result = $this->connect->multi_query($this->query);
        if (!$result) {
            $this->logErr('do_multi:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), true));

            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $query
     * @param string $key
     * @return mixed|null
     * @throws Exception
     */
    public function do_fromArray($query = '', $key = '')
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return null;
        }

        $key = trim($key);
        if (strlen($key) == 0) {
            $this->logErr('do_fromArray: empty key detected');
            return null;
        }


        $result = $this->do_one($this->query);

        if ($result && isset($result[$key]))
            return $result[$key];
        else
            return null;
    }

    /**
     * @param string $table
     * @param string $where
     * @param array $like
     * @return bool|int
     * @throws Exception
     */
    public function do_count($table = '', $where = '', $like = array())
    {

        //if not detected table name
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('do_count: empty table name detected');
            return false;
        }

        if (!is_array($like)) $likes[] = $like;//is is string or etc.
        else $likes = $like;


        if (is_array($where)) {
            $whereStr = '';
            $findArray = array();
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $tmp = '(';
                    for ($i = 0; $i < count($value); $i++) {
                        $val = $value[$i];


                        if (in_array($key, $likes)) {
                            $tmp .= ($i == 0 ? '' : ' or ') . '`' . $key . '` like :' . $key . '___' . $i;
                            $findArray[$key . '___' . $i] = '%' . $val . '%';
                        } else {
                            $tmp .= ($i == 0 ? '' : ' or ') . '`' . $key . '`=:' . $key . '___' . $i;
                            $findArray[$key . '___' . $i] = $val;
                        }

                    }
                    $tmp .= ')';
                } else {
                    if (in_array($key, $likes)) {
                        $tmp = '`' . $key . '` like :' . $key . '';
                        $findArray[$key] = '%' . $value . '%';
                    } else {
                        $tmp = '`' . $key . '`=:' . $key . '';
                        $findArray[$key] = $value;
                    }


                }


                if ($whereStr) $whereStr .= ' and ' . $tmp;
                else $whereStr = $tmp;
            }

            $this->query = 'SELECT count(*) c from ' . $table . ' WHERE ' . $whereStr;
            $PDO_query = $this->prepare($this->query);
            $result = $PDO_query->execute_one($findArray);

            if (isset($result['c']))
                return intval($result['c']);
            else
                return 0;


        } else {
            $whereStr = $where;

            $this->query = 'SELECT count(*) c from ' . $table . ' ' . ($where ? ' WHERE ' . $whereStr : '');
            $result = $this->do_one($this->query);
            return intval($result['c']);
        }


    }

    /**
     * @return int
     * @throws Exception
     */
    public function lastInsertId()
    {
        if (!$this->checkAll()) {
            return 0;
        }
        try {
            return $this->connect->lastInsertId();
        } catch (Exception $e) {
            $this->logErr('lastInsertId catch:<br>' . PHP_EOL . '--message: ' . $e->getMessage());
            return 0;
        }
    }


    /*** QUERY FUNCTIONS */

    /** PREPARE FUNCTIONS */

    /**
     * @param $query
     * @return PDOPrepared|null
     * @throws Exception
     */
    public function prepare($query)
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return null;
        }

        $PDO = new PDOPrepared;
        $PDO->connect = $this->connect;
        $PDO->prepare($this->query);

        return $PDO;
    }

    /**
     * @param $table
     * @param $where
     * @param array $array
     * @param int $limit
     * @return array|null
     * @throws Exception
     */
    public function prepareAndSelect($table, $where, $array = [], $limit = 0)
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareAndSelect: empty table detected');
            return null;
        }

        if ($this->connect == null) {
            $this->logErr('prepareAndSelect: connect is null');
            return null;
        }

        //GENERATE QUERY
        $structure = array();
        if ($array) {
            foreach ($array as $key) {
                $structure[] = '`' . $key . '`';
            }
            $structureStr = implode(',', $structure);
        } else {
            $structureStr = '*';
        }

        $whereStructure = array();
        $arrayKeys = array_keys($where);
        if ($arrayKeys) {
            foreach ($arrayKeys as $key) {
                $whereStructure[] = '`' . $key . '`=:' . $key;
            }
        }

        $this->query = 'SELECT ' . $structureStr . ' FROM `' . $table . '` where ' . implode(' and ', $whereStructure);

        if ($limit > 0) {
            $this->query .= ' LIMIT ' . $limit;
        }

        $PDO_prepare = new PDOPrepared;
        $PDO_prepare->connect = $this->connect;
        $PDO_prepare->prepare($this->query);

        return $PDO_prepare->execute($where);
    }

    /**
     * @param $table
     * @param $where
     * @param array $array
     * @param int $limit
     * @return array|mixed
     * @throws Exception
     */
    public function prepareAndSelectOne($table, $where, $array = [], $limit = 1)
    {
        $result = $this->prepareAndSelect($table, $where, $array, $limit);

        if ($result) {
            return $result[0];
        } else {
            return [];
        }
    }

    /**
     * @param $table
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function prepareAndInsert($table, $data)
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareAndSave: empty table detected');
            return false;
        }

        if (!is_array($data)) {
            $this->logErr('prepareAndSave: empty array detected');
            return false;
        } else if (count($data) == 0) {
            $this->logErr('prepareAndSave: array count = 0 detected');
            return false;
        }

        if ($this->connect == null) {
            $this->logErr('prepareAndSave: connect is null');
            return false;
        }

        $structure = array();
        $values = array();
        $arrayKeys = array_keys($data);
        if ($arrayKeys) {
            foreach ($arrayKeys as $key) {
                $structure[] = '`' . $key . '`';
                $values[] = ':' . $key . '';
            }
        }

        $this->query = 'INSERT INTO `' . $table . '`(' . implode(',', $structure) . ') VALUES (' . implode(',', $values) . ')';

        $PDO_prepare = new PDOPrepared;
        $PDO_prepare->connect = $this->connect;
        $PDO_prepare->prepare($this->query);

        return $PDO_prepare->execute_only($data);
    }

    /**
     * @param $table
     * @param $data
     * @param array $where
     * @param array $whereNot
     * @param int $limit
     * @return bool
     * @throws Exception
     */
    public function prepareAndUpdate($table, $data, $where = [], $whereNot = [], $limit = 1)
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareAndUpdate: empty table detected');
            return false;
        }

        if (!$data) {
            $this->logErr('prepareAndUpdate: empty DATA detected');
            return false;
        } elseif (!is_array($data)) {
            $this->logErr('prepareAndUpdate: DATA is not array');
            return false;
        }

        if (!$where && !$whereNot) {
            $this->logErr('prepareAndUpdate: empty WHERE detected');
            return false;
        } elseif (!is_array($data)) {
            $this->logErr('prepareAndUpdate: WHERE is not array');
            return false;
        }


        if ($this->connect == null) {
            $this->logErr('prepareAndUpdate: connect is null');
            return false;
        }

        $structure = array();
        $arrayKeys = array_keys($data);
        if ($arrayKeys) {
            foreach ($arrayKeys as $key) {
                $structure[] = '`' . $key . '`=:' . $key;
            }
        }
        $whereWithPrefix = [];
        $whereNotWithPrefix = [];
        $whereStructure = array();

        if ($where) {
            $arrayKeys = array_keys($where);
            $prefix = uniqid('DB_') . '_';
            if ($arrayKeys) {
                foreach ($arrayKeys as $key) {
                    $newKey = $prefix . $key;
                    $whereWithPrefix[$newKey] = $where[$key];
                    $whereStructure[] = '`' . $key . '`=:' . $newKey;
                }
            }
        }

        if ($whereNot) {
            $arrayKeys = array_keys($whereNot);
            $prefix = uniqid('DB2_') . '_';
            if ($arrayKeys) {
                foreach ($arrayKeys as $key) {
                    $newKey = $prefix . $key;
                    $whereNotWithPrefix[$newKey] = $whereNot[$key];
                    $whereStructure[] = '`' . $key . '`!=:' . $newKey;
                }
            }
        }


        $this->query = 'UPDATE `' . $table . '` SET ' . implode(',', $structure) . ' ';
        $this->query .= 'WHERE ' . implode(' AND ', $whereStructure) . ' ';
        if ($limit) {
            $this->query .= 'LIMIT ' . $limit;
        }

        $data = array_merge($data, $whereWithPrefix, $whereNotWithPrefix);
        $PDO_prepare = new PDOPrepared;
        $PDO_prepare->connect = $this->connect;
        $PDO_prepare->prepare($this->query);

        return $PDO_prepare->execute_only($data);
    }

    /**
     * @param $table
     * @param $array
     * @return PDOPrepared|null
     * @throws Exception
     */
    public function prepareInsert($table, $array)
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareInsert: empty table detected');
            return null;
        }

        if (!is_array($array)) {
            $this->logErr('prepareInsert: empty array detected');
            return null;
        } else if (count($array) == 0) {
            $this->logErr('prepareInsert: array count = 0 detected');
            return null;
        }

        if ($this->connect == null) {
            $this->logErr('prepareInsert: connect is null');
            return null;
        }

        //GENERATE QUERY
        $structure = array();
        $values = array();
        if ($array) {
            foreach ($array as $key) {
                $structure[] = '`' . $key . '`';
                $values[] = ':' . $key . '';
            }
        }

        $this->query = 'INSERT INTO `' . $table . '`(' . implode(',', $structure) . ') VALUES (' . implode(',', $values) . ')';

        $result = new PDOPrepared;
        $result->connect = $this->connect;
        $result->prepare($this->query);

        return $result;
    }

    /**
     * @param $table
     * @param $array
     * @param $where
     * @return PDOPrepared|null
     * @throws Exception
     */
    public function prepareUpdate($table, $array, $where)
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareUpdate: empty table detected');
            return null;
        }

        if (!is_array($array)) {
            $this->logErr('prepareUpdate: empty array detected');
            return null;
        } else if (count($array) == 0) {
            $this->logErr('prepareUpdate: array count = 0 detected');
            return null;
        }

        if ($this->connect == null) {
            $this->logErr('prepareUpdate: connect is null');
            return null;
        }

        //GENERATE QUERY
        $structure = array();
        if ($array) {
            foreach ($array as $key) {
                $structure[] = '`' . $key . '`=:' . $key . '';
            }
        }

        $this->query = 'UPDATE `' . $table . '` set ' . implode(',', $structure) . ' where ' . $where . '';

        $result = new PDOPrepared;
        $result->connect = $this->connect;
        $result->prepare($this->query);

        return $result;
    }


    /*** PREPARE FUNCTIONS */

    /** NOT PUBLIC FUNCTIONS */

    /**
     * @return bool
     * @throws Exception
     */
    protected function checkAll()
    {
        $localERR = '';
        $this->query = trim($this->query);
        if (strlen($this->query) == 0) {
            $localERR = 'empty query detected';
        } else if ($this->connect == null) {
            $localERR = 'connect is null';
        }

        if ($localERR) {
            $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : '???';

            $this->logErr($caller . ': ' . $localERR);
            return false;
        } else {
            return true;
        }
    }


    /**
     * @param $fetchData
     * @param bool $oneOnly
     * @return array
     */
    protected function do_fetch($fetchData, $oneOnly = false)
    {
        if ($fetchData != null) {
            //result is array
            if ($fetchData->rowCount() > 0) {
                $fetchDataResult = array();

                if ($oneOnly) {
                    $fetchDataResult = $fetchData->fetch(PDO::FETCH_ASSOC);
                } else {
                    $fetchDataResult = $fetchData->fetchAll(PDO::FETCH_ASSOC);
                }

                if (!$fetchDataResult) return array();

                return $fetchDataResult;
            } else {
                return array();
            }
        } else return array();
    }

    /**
     * @param $string
     * @throws Exception
     */
    protected function logErr($string)
    {
        try {
            Log::logError($string, self::LOG_FILE);
        } catch (Exception $e) {
            return;
        }

        return;
    }

    /*** NOT PUBLIC FUNCTIONS */
}
