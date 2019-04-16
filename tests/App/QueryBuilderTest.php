<?php
/** @noinspection PhpUndefinedMethodInspection */

namespace App;

include 'begin.php';

use PHPUnit\Framework\TestCase;

/* assertTrue, assertFalse, assertEmpty, assertEquals, assertCount, assertContains */

class QueryBuilderTest extends TestCase
{
    protected $testTable;
    private $db;

    protected function setUp()
    {
        /** @noinspection PhpIncludeInspection */
        $db_config = include ROOT . '/config/mysql.php';
        $pdo = new \PDO($db_config['DSN'], $db_config['username'], $db_config['password'], $db_config['pdo_options']);
        $this->db = new QueryBuilder($pdo);
        $this->testTable = 'tmp_18F987F1';
    }

    protected function tearDown()
    {
        $this->db = null;
    }

    /* selectRaw getCell statement */
    public function testStatement(): void
    {
        $assert = false;
        $need_result = $this->testTable;
        $sql = 'SHOW TABLES LIKE \'' . $this->testTable . '\'';
        $table = $this->db->selectRaw($sql)->getCell();
        if ($table !== $this->testTable) {
            $this->db->statement('CREATE TABLE ' . $this->testTable . ' (id INT (11), name VARCHAR (250))');
        }
        $result = $this->db->selectRaw($sql)->getCell();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* statement table insert select get */
    public function testInsert(): void
    {
        $assert = false;
        $need_result = [
            ['id' => 1, 'name' => 'test1'],
            ['id' => 2, 'name' => 'test2'],
            ['id' => 3, 'name' => 'test3'],
        ];
        $this->db->statement('TRUNCATE TABLE ' . $this->testTable);
        $data1 = ['id' => 1, 'name' => 'test1'];
        $data2 = [
            ['id' => 2, 'name' => 'test2'],
            ['id' => 3, 'name' => 'test3'],
        ];
        $this->db->table($this->testTable)->insert($data1);
        $this->db->table($this->testTable)->insert($data2);
        $result = $this->db->table($this->testTable)->select('id, name')->get();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* table where bind update select getCell */
    public function testUpdate(): void
    {
        $assert = false;
        $need_result = 'updated';
        $data = ['id' => 3];
        $update = ['name' => 'updated'];
        $this->db->table($this->testTable)->where('id = :id')->bind($data)->update($update);
        $result = $this->db->table($this->testTable)->select('name')
            ->where('id = :id')->bind($data)->getCell();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* table where bind updateOrInsert select getCell */
    public function testUpdateOrInsert_Update(): void
    {
        $assert = false;
        $need_result = 'updated2';
        $data = ['id' => 3];
        $update = ['name' => 'updated2'];
        $this->db->table($this->testTable)->where('id = :id')->bind($data)->updateOrInsert($update);
        $result = $this->db->table($this->testTable)->select('name')
            ->where('id = :id')->bind($data)->getCell();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* table where bind updateOrInsert select getCell */
    public function testUpdateOrInsert_Insert(): void
    {
        $assert = false;
        $need_result = 'test4';
        $data = ['id' => 4];
        $update = ['name' => 'test4'];
        $this->db->table($this->testTable)->where('id = :id')->bind($data)->updateOrInsert($update);
        $result = $this->db->table($this->testTable)->select('name')
            ->where('id = :id')->bind($data)->getCell();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* table where bind delete select getCell */
    public function testDelete(): void
    {
        $assert = false;
        $need_result = 0;
        $data = ['id' => 2];
        $this->db->table($this->testTable)->where('id = :id')->bind($data)->delete();
        $result = $this->db->table($this->testTable)->select('count(*)')
            ->where('id = :id')->bind($data)->getCell();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* table select where bind get */
    public function testSelectGet(): void
    {
        $assert = false;
        $need_result = [
            ['id' => 1, 'name' => 'test1'],
            ['id' => 3, 'name' => 'updated2'],
        ];
        $data = ['id' => 4];
        $result = $this->db->table($this->testTable)->select('id, name')
            ->where('id < :id')->bind($data)->get();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* table, select, where, groupBy, having, orderBy, getSQL */
    public function testConstructorGetSQL(): void
    {
        $assert = false;
        $need_result = file_get_contents(ROOT . '\tests\App\inc\testConstructorGetSQL.sql');

        $result = $this->db->table('country')
            ->select('IndepYear, COUNT(*) cnt')
            ->where('IndepYear IS NOT NULL')
            ->groupBy('IndepYear')
            ->having('COUNT(*) > 10')
            ->orderBy('IndepYear')
            ->getSQL();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* table orderBy first */
    public function testFirst(): void
    {
        $assert = false;
        $need_result = ['id' => 4, 'name' => 'test4'];
        $result = $this->db->table($this->testTable)->orderBy('id DESC')->first();
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* tale pluck */
    public function testPluck(): void
    {
        $assert = false;
        $need_result = [1, 3, 4];
        $result = $this->db->table($this->testTable)->pluck('id');
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    /* table pluck */
    public function testPluck_KeyValue(): void
    {
        $assert = false;
        $need_result = [1 => 'test1', 3 => 'updated2', 4 => 'test4'];
        $result = $this->db->table($this->testTable)->pluck('id', 'name');
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

    public function testDropTable(): void
    {
        $assert = false;
        $need_result = '00000';
        $result = $this->db->statement('DROP TABLE ' . $this->testTable);
        if ($need_result === $result) {$assert = true;} else {vd($need_result); vd($result);}
        $this->assertTrue($assert);
    }

}