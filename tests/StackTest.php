<?php

use GioLaza\Database\SqlDB;
use PHPUnit\Framework\TestCase;

const GIOLAZA_SHOW_ERRORS = true;
const GIOLAZA_SAVE_ERRORS = false;

final class StackTest extends TestCase
{
    /**
     * @var SqlDB
     */
    protected $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->db = new SqlDB;
        $this->db->connect('localhost', 'user', '123456', 'test');
    }

    public function testCanConnect()
    {
        $this->assertInstanceOf(PDO::class, $this->db->connect);
    }

    public function testCanSelectAll()
    {
        $this->assertIsArray($this->db->do_all('SELECT * FROM `tablea`'));
        $this->assertArrayHasKey(0, $this->db->do_all('SELECT * FROM `tablea`'));
    }

    public function testCanSelectOne()
    {
        $this->assertIsArray($this->db->do_one('SELECT * FROM `tablea`'));
        $this->assertArrayHasKey('name', $this->db->do_one('SELECT * FROM `tablea`'));
    }

    public function testCanUpdate()
    {
        $this->assertIsBool($this->db->do_only('UPDATE `tablea` SET `name`="TEST" WHERE `id`=3'));
    }

    public function testCanPrepareAndSelect()
    {
        $this->assertIsArray($this->db->prepareAndSelect('tablea', ['id' => 1]));
        $this->assertArrayHasKey(0, $this->db->prepareAndSelect('tablea', ['id' => 1]));
    }

    public function testCanPrepareAndSelectOne()
    {
        $this->assertIsArray($this->db->prepareAndSelectOne('tablea', ['id' => 1]));
        $this->assertArrayHasKey('name', $this->db->prepareAndSelectOne('tablea', ['id' => 1]));
    }

    public function testCanPrepareAndUpdate()
    {
        $this->assertIsBool($this->db->prepareAndUpdate('tablea', ['name' => rand()], ['id' => 1]));
    }
    public function testCanPrepareAndInsert()
    {
        $this->assertIsBool($this->db->prepareAndInsert('tablea', ['name' => rand()]));
    }
}