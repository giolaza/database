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

use Exception;
use GioLaza\Database\PDOPrepared as PDOPrepared;
use GioLaza\Logger\Log as Log;
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
     * Log file name
     */
    const LOG_FILE = 'engine.DBErrors.log';

    /**
     * @var null|PDO connection
     */
    public ?PDO $connect = null;

    /**
     * @var null|string
     */
    protected ?string $query = null;

    public function __construct()
    {
        if (!defined('GIOLAZA_SHOW_ERRORS')) {
            define('GIOLAZA_SHOW_ERRORS', false);
        }

        if (!defined('GIOLAZA_SAVE_ERRORS')) {
            define('GIOLAZA_SAVE_ERRORS', true);
        }
    }

    /**
     * @param $sqlHost
     * @param $sqlUser
     * @param $sqlPass
     * @param $sqlDbName
     * @return bool
     */
    public function connect(string $sqlHost, string $sqlUser, string $sqlPass, string $sqlDbName): bool
    {
        if (class_exists('PDO')) {
            try {
                $this->connect = new PDO(
                    'mysql:host=' . $sqlHost . ';dbname=' . $sqlDbName . ';charset=UTF8',
                    $sqlUser,
                    $sqlPass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_PERSISTENT => false
                    ]
                );
            } catch (Exception $e) {
                $this->logErr('db_open catch: ' . $e->getMessage());
                $this->connect = null;

                return false;
            }
        }
        else {
            $this->logErr('connect - check driver: class not found');

            return false;
        }

        // if detected error
        if (isset($this->connect->connect_errno) && $this->connect->connect_errno) {
            $this->logErr('db_open: ' . $this->connect->connect_error);
            $this->connect = null;

            return false;
        }

        return true;
    }

    /**
     * @param $string
     * @return void
     */
    protected function logErr($string)
    {
        try {
            Log::logError($string, self::LOG_FILE);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     *
     */
    public function db_close()
    {
        $this->connect = null;
    }

    /**
     * @return bool
     */
    public function checkConnection(): bool
    {
        return $this->connect instanceof PDO;
    }

    /**
     * @param string $query
     * @return bool
     */
    public function do_only(string $query = ''): bool
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
        }
        else {
            $this->logErr('do_only:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), true));
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function checkAll(): bool
    {
        $err = '';
        $this->query = trim($this->query);
        if (strlen($this->query) == 0) {
            $err = 'empty query detected';
        }
        else if ($this->connect == null) {
            $err = 'connect is null';
        }

        if ($err) {
            $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($dbt[1]['function']) ? $dbt[1]['function'] : '???';

            $this->logErr($caller . ': ' . $err);
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * @param string $query
     * @param string $key
     * @return array
     */
    public function do_allById(string $query = '', string $key = 'id'): array
    {
        return $this->do_allByKey($query, $key);
    }

    /**
     * @param string $query
     * @param string $key
     * @return array
     */
    public function do_allByKey(string $query = '', string $key = 'id'): array
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return [];
        }

        $result = [];

        $raw = $this->do_all($query);

        foreach ($raw as $row) {
            if (!isset($row[$key])) {
                continue;
            }

            $result[$row[$key]] = $row;
        }

        return $result;
    }


    /**
     * @param string $query
     * @return array
     */
    public function do_all(string $query = ''): array
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return [];
        }

        try {
            $result = $this->connect->query($this->query);
        } catch (Exception $e) {
            $this->logErr('do_all catch:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . $e->getMessage());

            return [];
        }

        if (!$result) {
            $this->logErr('do_all:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), true));

            return [];
        }
        else {
            return $this->doFetch($result);
        }
    }

    /**
     * @param $fetchData
     * @param bool $oneOnly
     * @return array
     */
    protected function doFetch($fetchData, bool $oneOnly = false): array
    {
        if ($fetchData != null && $fetchData->rowCount() > 0) {
            return $oneOnly ? $fetchData->fetch(PDO::FETCH_ASSOC) : $fetchData->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * @param string $query
     * @return bool
     */
    public function do_multi(string $query = ''): bool
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return false;
        }

        $result = $this->connect->multi_query($this->query);
        if (!$result) {
            $this->logErr('do_multi:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), true));

            return false;
        }
        else {
            return true;
        }
    }

    /**
     * @param string $query
     * @param string $key
     * @return mixed|null
     */
    public function do_fromArray(string $query = '', string $key = '')
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

        return $this->do_one($this->query)[$key] ?? null;
    }

    /**
     * @param string $query
     * @return array
     */
    public function do_one(string $query = ''): array
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return [];
        }

        try {
            $result = $this->connect->query($this->query);
        } catch (Exception $e) {
            $this->logErr('do_one catch:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . $e->getMessage());

            return [];
        }

        if (!$result) {
            $this->logErr('do_one:<br>' . PHP_EOL . '--query: ' . $this->query . '<br>' . PHP_EOL . '--message: ' . print_r($this->connect->errorInfo(), true));

            return [];
        }
        else {
            return $this->doFetch($result, true);
        }
    }

    /**
     * @param string $table
     * @param string $where
     * @param array $like
     * @return int
     */
    public function do_count(string $table = '', string $where = '', array $like = array()): int
    {
        //if not detected table name
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('do_count: empty table name detected');
            return 0;
        }

        if (!is_array($like)) $likes[] = $like;//is string or etc.
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
                        }
                        else {
                            $tmp .= ($i == 0 ? '' : ' or ') . '`' . $key . '`=:' . $key . '___' . $i;
                            $findArray[$key . '___' . $i] = $val;
                        }

                    }
                    $tmp .= ')';
                }
                else {
                    if (in_array($key, $likes)) {
                        $tmp = '`' . $key . '` like :' . $key . '';
                        $findArray[$key] = '%' . $value . '%';
                    }
                    else {
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


        }
        else {
            $whereStr = $where;

            $this->query = 'SELECT count(*) c from ' . $table . ' ' . ($where ? ' WHERE ' . $whereStr : '');
            $result = $this->do_one($this->query);
            return intval($result['c']);
        }


    }

    /**
     * @param $query
     * @return \GioLaza\Database\PDOPrepared|null
     */
    public function prepare(string $query)
    {
        $this->query = $query;
        if (!$this->checkAll()) {
            return null;
        }

        try {
            $PDO = new PDOPrepared;
            $PDO->connect = $this->connect;
            $PDO->prepare($this->query);

            return $PDO;
        } catch (Exception $e) {
            $this->logErr($e->getMessage());

            return null;
        }
    }

    /**
     * @return false|int|string
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

    /**
     * @param $table
     * @param $where
     * @param array $array
     * @param int $limit
     * @return array|mixed
     */
    public function prepareAndSelectOne($table, $where, array $array = [], int $limit = 1)
    {
        $result = $this->prepareAndSelect($table, $where, $array, $limit);

        if ($result) {
            return $result[0];
        }
        else {
            return [];
        }
    }

    /**
     * @param $table
     * @param $where
     * @param array $array
     * @param int $limit
     * @return array|null
     */
    public function prepareAndSelect($table, $where, array $array = [], int $limit = 0): ?array
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
        }
        else {
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

        try {
            $PDO_prepare = new PDOPrepared;
            $PDO_prepare->connect = $this->connect;
            $PDO_prepare->prepare($this->query);

            return $PDO_prepare->execute($where);
        } catch (Exception $e) {
            $this->logErr($e->getMessage());

            return [];
        }

    }

    /**
     * @param $table
     * @param $data
     * @return bool
     */
    public function prepareAndInsert($table, $data): bool
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareAndSave: empty table detected');
            return false;
        }

        if (!is_array($data)) {
            $this->logErr('prepareAndSave: empty array detected');
            return false;
        }
        else if (count($data) == 0) {
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
     */
    public function prepareAndUpdate($table, $data, array $where = [], array $whereNot = [], int $limit = 1): bool
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareAndUpdate: empty table detected');
            return false;
        }

        if (!$data) {
            $this->logErr('prepareAndUpdate: empty DATA detected');
            return false;
        }
        elseif (!is_array($data)) {
            $this->logErr('prepareAndUpdate: DATA is not array');
            return false;
        }

        if (!$where && !$whereNot) {
            $this->logErr('prepareAndUpdate: empty WHERE detected');
            return false;
        }
        elseif (!is_array($data)) {
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
     * @return \GioLaza\Database\PDOPrepared|null
     */
    public function prepareInsert($table, $array): ?\GioLaza\Database\PDOPrepared
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareInsert: empty table detected');
            return null;
        }

        if (!is_array($array)) {
            $this->logErr('prepareInsert: empty array detected');
            return null;
        }
        else if (count($array) == 0) {
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
     * @return \GioLaza\Database\PDOPrepared|null
     */
    public function prepareUpdate($table, $array, $where): ?\GioLaza\Database\PDOPrepared
    {
        $table = trim($table);
        if (strlen($table) == 0) {
            $this->logErr('prepareUpdate: empty table detected');
            return null;
        }

        if (!is_array($array)) {
            $this->logErr('prepareUpdate: empty array detected');
            return null;
        }
        else if (count($array) == 0) {
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
}
