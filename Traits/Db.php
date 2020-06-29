<?php

namespace GioLaza\Database\Traits;

/**
 * Trait Db
 * @package GioLaza\Database\Traits
 */
trait Db
{
    /**
     * @var GioLaza\Database\SqlDB
     */
    protected $db = null;

    /**
     * Set DB driver in class
     *
     * @param $DB
     */
    public function setDB(&$DB)
    {
        $this->db = $DB;
    }

    /**
     * Check DB driver in class
     */
    protected function checkDB()
    {
        if ($this->db === null) {
            $this->logErr('No DB connection found');
        }
    }
}
