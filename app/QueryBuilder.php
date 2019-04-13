<?php

/*
 * select($sql, $data)->get();
 * insert($sql, $data);
 * update($sql, $data);
 * delete($sql, $data);
 * statement($sql, $data);
 *
 * ��������������/��������� SQL ��������
 * listen()-select()->get;
 *
 * ����������� SQL ��������
 * enableQueryLog();
 * disableQueryLog();
 *
 * beginTransaction();
 * commit();
 * rollback();
 *
 * table($tableName)->get();
 * ->field()
 * ->where()
 * ->groupBy()
 * ->having()
 * ->orderBy()
 * ->bind($data)
 *
 * ->get();
 * ->getCell();
 * ->first();
 * ->pluck();
 *
 * getSQL();
 * getLastInsertId();
 * getAffectedRows(); ���������� ���������� ������� ����� INSERT, UPDATE, DELETE
 * getTimeExecution();
 */

namespace App;

class QueryBuilder
{
    protected $pdo;
    protected $bindValues = [];
    protected $sql;
    protected $method;
    protected $tableSQL;
    protected $fieldSQL;
    protected $whereSQL;
    protected $groupBySQL;
    protected $havingSQL;
    protected $orderSQL;

    public function __construct (\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fieldSQL = 'SELECT *';
    }

    public function select ($sql, $data = [])
    {
        $this->sql = $sql;
        $this->bindValues = $data;
        $this->method = 'Raw';
        return $this;
    }

    public function table ($string)
    {
        $this->method = 'Constructor';
        $this->fieldSQL = 'SELECT *';
        $this->tableSQL = 'FROM ' . $string;
        $this->whereSQL = null;
        $this->groupBySQL = null;
        $this->havingSQL = null;
        $this->orderSQL = null;
        $this->bindValues = [];
        return $this;
    }

    public function field ($string = '*')
    {
        $this->fieldSQL = 'SELECT ' . $string;
        return $this;
    }

    public function where ($string)
    {
        $this->whereSQL = 'WHERE ' . $string;
        return $this;
    }

    public function groupBy ($string)
    {
        $this->groupBySQL = 'GROUP BY ' . $string;
        return $this;
    }

    public function having ($string)
    {
        $this->havingSQL = 'HAVING ' . $string;
        return $this;
    }

    public function orderBy ($string)
    {
        $this->orderSQL = 'ORDER BY ' . $string;
        return $this;
    }

    public function bind (array $data = [])
    {
        $this->bindValues = $data;
        return $this;
    }

    /* �������� ��� ������ */
    public function get (): array
    {
        $sql = $this->getSQL();
        $stmt = $this->execute($sql, $this->bindValues);
        return $stmt->fetchAll();
    }

    /* �������� �������� ������� ������� ������ ������ */
    public function getCell ($field = '')
    {
        $this->fieldSQL = 'SELECT ' . $field;
        $sql = $this->getSQL();
        $stmt = $this->execute($sql, $this->bindValues);
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return $row[0];
    }

    /* �������� ������ ������ */
    public function first (): array
    {
        $sql = $this->getSQL();
        $stmt = $this->execute($sql, $this->bindValues);
        return $stmt->fetch();
    }

    /* �������� ������ �������� ������ ������� */
    public function pluck ($field): array
    {
        $this->fieldSQL = 'SELECT ' . $field;
        $sql = $this->getSQL();
        $stmt = $this->execute($sql, $this->bindValues);
        $rows = $stmt->fetchAll(\PDO::FETCH_NUM);
        $result = [];
        foreach ($rows as $row) {
            $result[] = $row[0];
        }
        return $result;
    }

    public function getSQL (): string
    {
        if ($this->method === 'Raw') {return $this->sql;}

        $cr = chr(13) . chr(10);
        $sql = $this->fieldSQL . $cr . $this->tableSQL;
        if ($this->whereSQL !== null) {$sql .= $cr . $this->whereSQL;}
        if ($this->groupBySQL !== null) {$sql .= $cr . $this->groupBySQL;}
        if ($this->havingSQL !== null) {$sql .= $cr . $this->havingSQL;}
        if ($this->orderSQL !== null) {$sql .= $cr . $this->orderSQL;}

        return $sql;
    }

    protected function execute ($sql, array $data = [])
    {
        $stmt = null;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return $stmt;
    }

}