<?php

/*
 *** execute($sql, $data)->get();
 * statement($sql, $data);
 *
 * Журналирование/прослушка SQL запросов
 *** listen()-select()->get;
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
 *** ->select()
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
 *** ->pluck();
 *
 *** getSQL();
 *** getLastInsertId();
 *** getAffectedRows(); Количество измененных записей после INSERT, UPDATE, DELETE
 *** getTimeExecution();
 */

namespace App;

class QueryBuilder
{
    protected $affectedRows;
    protected $bindData = [];
    protected $cr;
    protected $fieldSQL;
    protected $groupBySQL;
    protected $havingSQL;
    protected $lastInsertId;
    protected $listenSQL;
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

    public function execute ($sql, $data = [])
    {
        $this->sql = $sql;
        $this->bindData = $data;
        $this->method = 'Raw';
        $this->listenSQL = false;
        return $this;
    }

    public function table ($string)
    {
        $this->method = 'Constructor';
        $this->listenSQL = false;
        $this->fieldSQL = '*';
        $this->tableSQL = $string;
        $this->whereSQL = null;
        $this->groupBySQL = null;
        $this->havingSQL = null;
        $this->orderSQL = null;
        $this->bindData = [];
        $this->affectedRows = 0;
        return $this;
    }

    public function select ($string = '*')
    {
        $this->fieldSQL = $string;
        return $this;
    }

    public function listen ()
    {
        $this->listenSQL = true;
        return $this;
    }

    public function where ($data)
    {
        $this->whereSQL = $data;

        if (is_array($data)) {
            $where_string = $this->parametersString($data);
            $this->whereSQL = $where_string;
        }
        return $this;
    }

    public function groupBy ($string)
    {
        $this->groupBySQL = $string;
        return $this;
    }

    public function having ($string)
    {
        $this->havingSQL = $string;
        return $this;
    }

    public function orderBy ($string)
    {
        $this->orderSQL = $string;
        return $this;
    }

    public function bind (array $data = [])
    {
        $this->bindData = $data;
        return $this;
    }

    public function insert (array $data): int
    {
        $this->lastInsertId = 0;
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
        $this->lastInsertId = $this->pdo->lastInsertId();
        return $stmt->rowCount();
    }

    public function delete (array $data): int
    {
        $cr = $this->cr;
        $string = $this->parametersString($data);
        /** @noinspection SqlWithoutWhere */
        $sql = 'DELETE FROM ' . $this->tableSQL . $cr . 'WHERE ' . $string;
        $stmt = $this->executeSQL($sql, $data);
        if ($stmt === null) {return 0;}
        $this->affectedRows = $stmt->rowCount();
        return $this->affectedRows;
    }

    /*
        Пример использования:
    $where = ['id' => '22'];
    $update = ['name' => 'qqqq'];
    $affectedRows = $db->table('test')->where('id = :id')
        ->bind(array_merge($where, $update))->update($update);
    */
    public function update (array $data): int
    {
        $cr = $this->cr;
        $keys = array_keys($data);
        $string = '';
        foreach ($keys as $key) {$string .= $key . ' = :' . $key . ', ';}
        $update_string = rtrim($string, ', ');
        $sql = 'UPDATE ' . $this->tableSQL . $cr . 'SET ' . $update_string . $cr . 'WHERE ' . $this->whereSQL;
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return 0;}
        $this->affectedRows = $stmt->rowCount();
        return $this->affectedRows;
    }

    public function updateOrInsert (array $data): int
    {
        // TODO зупинився на цьому методі
        $sql = 'SELECT COUNT(*) FROM ' . $this->tableSQL . ' WHERE ' . $this->whereSQL;
        $stmt = $this->executeSQL($sql, $this->bindData);
        $rows = $stmt->fetch(\PDO::FETCH_NUM);
        $count = $rows[0];

        if ($count === 0) {
            $this->affectedRows = $this->insert($data);
        } else {
            $this->affectedRows = $this->update($data);
        }
        return $this->affectedRows;
    }

    /* Получить все записи */
    public function get (): array
    {
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return [];}
        return $stmt->fetchAll();
    }

    /* Получить значение первого столпца первой строки */
    public function getCell ($field = '')
    {
        $this->fieldSQL = $field;
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return null;}
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return $row[0];
    }

    /* Получить первую строку */
    public function first (): array
    {
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return [];}
        return $stmt->fetch();
    }

    /* Получить массив значений одного столбца (если два столбца то пара ключ-значение) */
    public function pluck ($key, $value = null): array
    {
        if ($value === null) {$this->fieldSQL = $key;} else {$this->fieldSQL = $key . ', ' . $value;}
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return [];}
        $rows = $stmt->fetchAll();
        $result = [];
        if ($value === null) {
            foreach ($rows as $row) {$result[] = $row[$key];}
        } else {
            foreach ($rows as $row) {$result[$row[$key]] = $row[$value];}
        }
        return $result;
    }

    public function getSQL (): string
    {
        if ($this->method === 'Raw') {return $this->sql;}

        $cr = chr(13) . chr(10);
        $sql = 'SELECT ' . $this->fieldSQL . $cr . 'FROM ' . $this->tableSQL;
        if ($this->whereSQL !== null) {$sql .= $cr . 'WHERE ' . $this->whereSQL;}
        if ($this->groupBySQL !== null) {$sql .= $cr . 'GROUP BY ' . $this->groupBySQL;}
        if ($this->havingSQL !== null) {$sql .= $cr . 'HAVING ' . $this->havingSQL;}
        if ($this->orderSQL !== null) {$sql .= $cr . 'ORDER BY ' . $this->orderSQL;}

        return $sql;
    }

    public function getAffectedRows ()
    {
        return $this->affectedRows;
    }

    public function getLastInsertId ()
    {
        return $this->lastInsertId;
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
        $this->lastInsertId = 0;

        if ($this->listenSQL) {vd($sql, $data);}

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($data as $key => $value) {$stmt->bindValue($key, $value);}
            $stmt->execute();
            $this->sql_time = round(microtime(true) - $start_time, 4);
            if ($this->listenSQL) {vd('SQL time: ' . $this->sql_time);}
        } catch (\Exception $e) {
            if ($this->listenSQL) {echo $e->getMessage();}
            Log::save(debug_backtrace(), [$e->getMessage(), $sql, $data]);
        }

        return $stmt;
    }

    protected function parametersString (array $data): string
    {
        $string = '';
        $keys = array_keys($data);

        foreach ($keys as $key) {$string .= $key . ' = :' . $key . ' AND ';}
        $string = substr($string, 0,-5);

        return $string;
    }

}