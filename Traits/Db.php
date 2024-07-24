<?php

namespace GioLaza\Database\Traits;

use Exception;
use GioLaza\Database\SqlDB;

/**
 * Trait Db
 * @package GioLaza\Database\Traits
 */
trait Db
{
    /**
     * @var null|\GioLaza\Database\SqlDB
     */
    protected ?SqlDB $db = null;

    /**
     * Set DB driver in class
     *
     * @param \GioLaza\Database\SqlDB &$database
     */
    public function setDB(SqlDB &$database)
    {
        $this->db = $database;
    }

    /**
     * Check DB driver in class
     *
     * @throws \Exception if no DB connection found
     */
    protected function checkDB(): void
    {
        if (is_null($this->db)) {
            throw new Exception('No DB connection found');
        }
    }
}
