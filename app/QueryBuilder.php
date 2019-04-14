<?php

/*
 * select($sql, $data)->get();
 * statement($sql, $data);
 *
 * Журналирование/прослушка SQL запросов
 * listen()-select()->get;
 *
 * Логирование SQL запросов
 * enableQueryLog();
 * disableQueryLog();
 *
 * beginTransaction();
 * commit();
 * rollback();
 *
 * table($tableName)->get();
 *** ->field()
 *** ->where()
 *** ->groupBy()
 *** ->having()
 *** ->orderBy()
 *** ->bind($data)
 *** insert($sql, $data);
 *** update($sql, $data);
 * updateOrInsert($sql, $data);
 *** delete($sql, $data);
 *
 *** ->get();
 *** ->getCell();
 *** ->first();
 * ->pluck();
 *
 *** getSQL();
 * getLastInsertId();
 *** getAffectedRows(); Количество измененных записей после INSERT, UPDATE, DELETE
 *** getTimeExecution();
 */

namespace App;

class QueryBuilder
{
    protected $affectedRows;
    protected $bindValues = [];
    protected $cr;
    protected $fieldSQL;
    protected $groupBySQL;
    protected $havingSQL;
    protected $method; // конструктор или сырой RAW
    protected $orderSQL;
    protected $pdo;
    protected $sql;
    protected $sql_time;
    protected $tableSQL;
    protected $whereSQL;

    public function __construct (\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fieldSQL = '*';
        $this->cr = chr(13) . chr(10);
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
        $this->fieldSQL = '*';
        $this->tableSQL = $string;
        $this->whereSQL = null;
        $this->groupBySQL = null;
        $this->havingSQL = null;
        $this->orderSQL = null;
        $this->bindValues = [];
        $this->affectedRows = 0;
        return $this;
    }

    public function field ($string = '*')
    {
        $this->fieldSQL = $string;
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

    public function insert (array $data): int
    {
        if (isset($data[0]) && is_array($data[0])) {
            $this->affectedRows = 0;
            foreach ($data as $item) {
                $this->affectedRows += $this->insertData($item);
            }
            return $this->affectedRows;
        }

        $this->affectedRows = $this->insertData($data);
        return $this->affectedRows;
    }

    protected function insertData (array $data): int
    {
        $cr = $this->cr;
        $keys = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));
        $sql = 'INSERT INTO ' . $this->tableSQL . ' (' .$keys . ')' . $cr . 'VALUES (' . $values . ')';
        $stmt = $this->executeSQL($sql, $data);
        if ($stmt === null) {return 0;}
        return $stmt->rowCount();
    }

    public function delete (array $data): int
    {
        $cr = $this->cr;
        $string = $this->ParametersString($data);
        /** @noinspection SqlWithoutWhere */
        $sql = 'DELETE FROM ' . $this->tableSQL . $cr . 'WHERE ' . $string;
        $stmt = $this->executeSQL($sql, $data);
        if ($stmt === null) {return 0;}
        $this->affectedRows = $stmt->rowCount();
        return $this->affectedRows;
    }

    public function update (array $update, array $where): int
    {
        $cr = $this->cr;
        $keys = array_keys($update);
        $string = '';
        foreach ($keys as $key) {$string .= $key . ' = :' . $key . ', ';}
        $keys = rtrim($string, ', ');
        $data = array_merge($update, $where);
        $where_string = $this->ParametersString($where);
        $sql = 'UPDATE ' . $this->tableSQL . $cr . 'SET ' . $keys . $cr . 'WHERE ' . $where_string;
        $stmt = $this->executeSQL($sql, $data);
        if ($stmt === null) {return 0;}
        $this->affectedRows = $stmt->rowCount();
        return $this->affectedRows;
    }

    /* Получить все записи */
    public function get (): array
    {
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindValues);
        if ($stmt === null) {return [];}
        return $stmt->fetchAll();
    }

    /* Получить значение первого столпца первой строки */
    public function getCell ($field = '')
    {
        $this->fieldSQL = 'SELECT ' . $field;
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindValues);
        if ($stmt === null) {return null;}
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return $row[0];
    }

    /* Получить первую строку */
    public function first (): array
    {
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindValues);
        if ($stmt === null) {return [];}
        return $stmt->fetch();
    }

    /* Получить массив значений одного столбца */
    public function pluck ($key, $value = null): array
    {
        // TODO зробити метод pluck для одного та двох аргументів
        $this->fieldSQL = 'SELECT ' . $key;
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindValues);
        if ($stmt === null) {return [];}
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
        $sql = 'SELECT ' . $this->fieldSQL . $cr . 'FROM ' . $this->tableSQL;
        if ($this->whereSQL !== null) {$sql .= $cr . $this->whereSQL;}
        if ($this->groupBySQL !== null) {$sql .= $cr . $this->groupBySQL;}
        if ($this->havingSQL !== null) {$sql .= $cr . $this->havingSQL;}
        if ($this->orderSQL !== null) {$sql .= $cr . $this->orderSQL;}

        return $sql;
    }

    public function getAffectedRows ()
    {
        return $this->affectedRows;
    }

    public function getTimeExecution ()
    {
        return $this->sql_time;
    }

    protected function executeSQL ($sql, array $data = [])
    {
        $stmt = null;
        $start_time = microtime(true);
        $this->sql_time = null;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            $this->sql_time = round(microtime(true) - $start_time, 4);
        } catch (\Exception $e) {
            Log::save(debug_backtrace(), [$e->getMessage(), $sql, $data]);
        }

        return $stmt;
    }

    protected function ParametersString (array $data): string
    {
        $string = '';
        $keys = array_keys($data);

        foreach ($keys as $key) {$string .= $key . ' = :' . $key . ' AND ';}
        $string = substr($string, 0,-5);

        return $string;
    }

}