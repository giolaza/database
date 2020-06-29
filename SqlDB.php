<?php
/**
 * SqlDB.php
 *
 * @category   DB
 * @package    GL
 * @author     Giorgi Lazashvili <giolaza@gmail.com>
 * @version    2 (26 MAY 2018)
 *
 ****************************************************************************************************
 *
 * --REQUIREMENTS--
 *
 * const DB_Driver='PDO'; - "PDO" for pdo connection, another connection is not supported
 * const LOG_FILE='engine.DBErros.php'; - file to save errors
 *
 *
 * --METHODS--
 * (object) sqlDB->connect($sql_host,$sql_user,$sql_pass,$sql_db_name) - db connection open, calls db_open
 * (object) sqlDB->db_open($sql_host,$sql_user,$sql_pass,$sql_db_name) - db connection open
 * (void) sqlDB->db_close() - close connection
 * (boolean) sqlDB->check_connection() - check connection
 * (boolean) sqlDB->do_only($query) - sql run only without checking output, recommended for UPDATE, DELETE, etc...
 * (array) sqlDB->do_one($query) - recommended for SELECT ... LIMIT 1, RESULT -  $result['key1'],$result['key2'],$result['key3']...
 * (array) sqlDB->do_all($query) - recommended for SELECT ..., RESULT -
 * $result[0]['key1'],$result[0]['key2'],$result[0]['key3']...
 * $result[1]['key1'],$result[1]['key2'],$result[1]['key3']...
 * ...
 * (mixed) sqlDB->do_fromArray($query,$key) recommended for SELECT ... LIMIT 1, return $result[$key] , uses sqlDB->do_one($query)
 * (int) sqlDB->do_count($table,$where='',$like=array()) recommended for SELECT count(*) from table where....  return intval(count) , uses PDO for prepare is where is ARRAY
 * or simple query if is string or nothing.
 * $where can be null;
 * $like is array using only in PDO query,in array is structure name
 * that will be used with LIKE %x% operators
 * (boolean) sqlDB->do_multi($query) recommended for multi query UPDATE1 ... LIMIT 1;UPDATE2 ... LIMIT 1; if multi query not returns error result will be true;
 *
 *
 *
 * (object - PDOPrepared) sqlDB->prepare($query) - creates new PDO object, ready for execute
 * (object - PDOPrepared) sqlDB->prepareInsert($table, $structureArray=[using this value in foreach]) - creates new PDO object with INSERT INTO table (str) VALUES (str)
 * (object - PDOPrepared) sqlDB->prepareUpdate($table, $structureArray=[using this value in foreach], $whereString) - creates new PDO object - creates new PDO object with UPDATES table set str=:str where string
 ****************************************************************************************************
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
        if (!defined('engineShowErrors')) {
            define('engineShowErrors', false, 1);
        }

        if (!defined('engineSaveErrors')) {
            define('engineSaveErrors', true, 1);
        }

        if (!function_exists('lang')) {
            function lang($str)
            {
                return $str;
            }
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
                    $this->logErr('db_open check driver: ' . lang('connection type not supported by engine'));
                    $this->connect = null;
                    return false;
                }
            } catch (Exception $e) {
                $this->logErr('db_open catch: ' . $e->getMessage());
                $this->connect = null;
                return false;
            }
        } else {
            $this->logErr('db_open check driver: ' . lang('class not found'));
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
                $this->logErr('db_open: ' . lang('false connection'));
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
            $this->logErr('db_close: ' . lang('connect is null'));
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
            $this->logErr('do_fromArray: ' . lang('empty key detected'));
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
            $this->logErr('do_count: ' . lang('empty table name detected'));
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

            $query = 'SELECT count(*) c from ' . $table . ' WHERE ' . $whereStr;
            $PDO_query = $this->prepare($query);
            $result = $PDO_query->execute_one($findArray);

            if (isset($result['c']))
                return intval($result['c']);
            else
                return 0;


        } else {
            $whereStr = $where;

            $query = 'SELECT count(*) c from ' . $table . ' ' . ($where ? ' WHERE ' . $whereStr : '');
            $result = $this->do_one($query);
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
            $this->logErr('prepareAndSelect: ' . lang('empty table detected'));
            return null;
        }

        if ($this->connect == null) {
            $this->logErr('prepareAndSelect: ' . lang('connect is null'));
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

        $query = 'SELECT ' . $structureStr . ' FROM `' . $table . '` where ' . implode(' and ', $whereStructure);

        if ($limit > 0) {
            $query .= ' LIMIT ' . $limit;
        }

        $PDO_prepare = new PDOPrepared;
        $PDO_prepare->connect = $this->connect;
        $PDO_prepare->prepare($query);

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
            $this->logErr('prepareAndSave: ' . lang('empty table detected'));
            return false;
        }

        if (!is_array($data)) {
            $this->logErr('prepareAndSave: ' . lang('empty array detected'));
            return false;
        } else if (count($data) == 0) {
            $this->logErr('prepareAndSave: ' . lang('array count = 0 detected'));
            return false;
        }

        if ($this->connect == null) {
            $this->logErr('prepareAndSave: ' . lang('connect is null'));
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

        $query = 'INSERT INTO `' . $table . '`(' . implode(',', $structure) . ') VALUES (' . implode(',', $values) . ')';

        $PDO_prepare = new PDOPrepared;
        $PDO_prepare->connect = $this->connect;
        $PDO_prepare->prepare($query);

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
            $this->logErr('prepareAndUpdate: ' . lang('empty table detected'));
            return false;
        }

        if (!$data) {
            $this->logErr('prepareAndUpdate: ' . lang('empty DATA detected'));
            return false;
        } elseif (!is_array($data)) {
            $this->logErr('prepareAndUpdate: ' . lang('DATA is not array'));
            return false;
        }

        if (!$where && !$whereNot) {
            $this->logErr('prepareAndUpdate: ' . lang('empty WHERE detected'));
            return false;
        } elseif (!is_array($data)) {
            $this->logErr('prepareAndUpdate: ' . lang('WHERE is not array'));
            return false;
        }


        if ($this->connect == null) {
            $this->logErr('prepareAndUpdate: ' . lang('connect is null'));
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


        $query = 'UPDATE `' . $table . '` SET ' . implode(',', $structure) . ' ';
        $query .= 'WHERE ' . implode(' AND ', $whereStructure) . ' ';
        if ($limit) {
            $query .= 'LIMIT ' . $limit;
        }

        $data = array_merge($data, $whereWithPrefix, $whereNotWithPrefix);
        $PDO_prepare = new PDOPrepared;
        $PDO_prepare->connect = $this->connect;
        $PDO_prepare->prepare($query);

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
            $this->logErr('prepareInsert: ' . lang('empty table detected'));
            return null;
        }

        if (!is_array($array)) {
            $this->logErr('prepareInsert: ' . lang('empty array detected'));
            return null;
        } else if (count($array) == 0) {
            $this->logErr('prepareInsert: ' . lang('array count = 0 detected'));
            return null;
        }

        if ($this->connect == null) {
            $this->logErr('prepareInsert: ' . lang('connect is null'));
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

        $query = 'INSERT INTO `' . $table . '`(' . implode(',', $structure) . ') VALUES (' . implode(',', $values) . ')';

        $result = new PDOPrepared;
        $result->connect = $this->connect;
        $result->prepare($query);

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
            $this->logErr('prepareUpdate: ' . lang('empty table detected'));
            return null;
        }

        if (!is_array($array)) {
            $this->logErr('prepareUpdate: ' . lang('empty array detected'));
            return null;
        } else if (count($array) == 0) {
            $this->logErr('prepareUpdate: ' . lang('array count = 0 detected'));
            return null;
        }

        if ($this->connect == null) {
            $this->logErr('prepareUpdate: ' . lang('connect is null'));
            return null;
        }

        //GENERATE QUERY
        $structure = array();
        if ($array) {
            foreach ($array as $key) {
                $structure[] = '`' . $key . '`=:' . $key . '';
            }
        }

        $query = 'UPDATE `' . $table . '` set ' . implode(',', $structure) . ' where ' . $where . '';

        $result = new PDOPrepared;
        $result->connect = $this->connect;
        $result->prepare($query);

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
            $localERR = lang('empty key detected');
        } else if ($this->connect == null) {
            $localERR = lang('connect is null');
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
