<?php
/** @noinspection PhpUndefinedMethodInspection */

namespace App;

include 'begin.php';

use PHPUnit\Framework\TestCase;

/* assertTrue, assertFalse, assertEmpty, assertEquals, assertCount, assertContains */

class QueryBuilderTest extends TestCase
{
    private $db;
    private $root;

    protected function setUp()
    {
        $root = $_SERVER['DOCUMENT_ROOT'];
        if ($root === '') {$root = 'D:/www/qb.loc';}
        $db_config = include $root . '/config/mysql.php';
        $pdo = new \PDO($db_config['DSN'], $db_config['username'], $db_config['password'], $db_config['pdo_options']);
        $this->db = new QueryBuilder($pdo);
        $this->root = $root;
    }

    protected function tearDown()
    {
        $this->db = null;
    }

    /* execute, bind, get, (getSQK, executeSQL) */
    public function testSelectGet(): void
    {
        $assert = false;
        $need_result = [
            ['Code' => 'GBR', 'Name' => 'United Kingdom', 'IndepYear' => 1066],
            ['Code' => 'UKR', 'Name' => 'Ukraine', 'IndepYear' => 1991],
        ];
        $data = ['code1' => 'UKR', 'code2' => 'GBR'];
        $sql = 'SELECT Code, Name, IndepYear FROM country WHERE Code = :code1 OR Code = :code2';
        $result = $this->db->execute($sql)->bind($data)->get();
        if ($need_result === $result) {$assert = true;}
            else {vd2($need_result); vd2($result);}
        $this->assertTrue($assert);
    }

    /* table, select, where, groupBy, having, orderBy, getSQL */
    public function testConstructorGetSQL(): void
    {
        $assert = false;
        $need_result = file_get_contents($this->root . '\tests\App\inc\testConstructorGetSQL.sql');

        $result = $this->db->table('country')
            ->select('IndepYear, COUNT(*) cnt')
            ->where('IndepYear IS NOT NULL')
            ->groupBy('IndepYear')
            ->having('COUNT(*) > 10')
            ->orderBy('IndepYear')
            ->getSQL();
        if ($need_result === $result) {$assert = true;}
            else {vd2($need_result); vd2($result);}
        $this->assertTrue($assert);
    }

    public function testFirst(): void
    {
        $assert = false;
        $need_result = ['Code' => 'CHN', 'Name' => 'China', 'IndepYear' => -1523];
        $sql = 'SELECT Code, Name, IndepYear FROM country WHERE IndepYear IS NOT NULL ORDER BY IndepYear';
        $result = $this->db->execute($sql)->first();
        if ($need_result === $result) {$assert = true;}
            else {vd2($need_result); vd2($result);}
        $this->assertTrue($assert);
    }

    public function testPluck(): void
    {
        $assert = false;
        $need_result = ['Canada', 'United States'];
        $data = ['region' => 'North America'];
        $sql = 'SELECT name FROM country WHERE region = :region AND population > 100000 ORDER BY Name';
        $result = $this->db->execute($sql)->bind($data)->pluck('name');
        if ($need_result === $result) {$assert = true;}
            else {vd2($need_result); vd2($result);}
        $this->assertTrue($assert);
    }

    public function testPluck2(): void
    {
        $assert = false;
        $need_result = ['CAN' => 'Canada', 'USA' => 'United States'];
        $data = ['region' => 'North America'];
        $sql = 'SELECT code, name FROM country WHERE region = :region AND population > 100000 ORDER BY Name';
        $result = $this->db->execute($sql)->bind($data)->pluck('code', 'name');
        if ($need_result === $result) {$assert = true;}
        else {vd2($need_result); vd2($result);}
        $this->assertTrue($assert);
    }

    public function testGetCell(): void
    {
        $assert = false;
        $need_result = 'Ukraine';
        $data = ['code' => 'UKR'];
        $sql = 'SELECT Name FROM country WHERE Code = :code';
        $result = $this->db->execute($sql)->bind($data)->getCell();
        if ($need_result === $result) {$assert = true;}
            else {vd2($need_result); vd2($result);}
        $this->assertTrue($assert);
    }
}