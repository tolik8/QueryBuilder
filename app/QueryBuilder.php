<?php

/*
 * Знак "+" означает что метод покрыт тестами

 + statement(string $sql, array $data);   return '00000' if everything is Ok
 + selectRaw(string $sql, array $data)->

 + table(string $tableName)->
 + ->select(string)->
 + ->where(string)->
 + ->groupBy(string)->
 + ->having(string)->
 + ->orderBy(string)->
 + ->bind(array $data)->
 * ->listen()->

    return array
 + ->get();
 + ->first();
 + ->pluck(string $key [string $value] );
 + ->getCell( [string $fieldName] );

    return affected rows
 + ->insert(array $data);
 + ->update(array $dataForUpdate);
 + ->updateOrInsert(array $dataForUpdate);
 + ->delete();

 * getAffectedRows();
 * getErrorsCount();
 * getLastInsertId();
 * getNewUUID();
 + getSQL();
 * getTimeExecution();

 * beginTransaction();
 * endTransaction();
 * commit();
 * rollback();

 * enableQueryLog(string $logName);
 * disableQueryLog();
 * clearQueryLog(string $logName);
 */

namespace App;

class QueryBuilder
{
    protected $affectedRows;
    protected $bindData = [];
    protected $bindDataForUpdate = [];
    protected $errors_before_transaction = 0;
    protected $errors_count = 0;
    protected $fieldSQL;
    protected $groupBySQL;
    protected $havingSQL;
    protected $lastInsertId;
    protected $listenSQL;
    protected $logName;
    protected $logOverwrite;
    protected $method; // конструктор table или сырой selectRaw
    protected $orderSQL;
    protected $pdo;
    protected $queryLog = false;
    protected $sql;
    protected $sql_time;
    protected $tableSQL;
    protected $whereSQL;

    public function __construct (\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->fieldSQL = '*';
    }

    public function statement (string $sql, array $data = []): string
    {
        $stmt = $this->executeSQL($sql, $data);
        return $stmt->errorCode();
    }

    public function selectRaw (string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->bindData = $data;
        $this->method = 'Raw';
        $this->listenSQL = false;
        return $this;
    }

    public function table (string $tableName)
    {
        $this->method = 'Constructor';
        $this->listenSQL = false;
        $this->fieldSQL = '*';
        $this->tableSQL = $tableName;
        $this->whereSQL = null;
        $this->groupBySQL = null;
        $this->havingSQL = null;
        $this->orderSQL = null;
        $this->bindData = [];
        $this->affectedRows = 0;
        return $this;
    }

    public function select (string $fields = '*')
    {
        $this->fieldSQL = $fields;
        return $this;
    }

    public function where (string $where)
    {
        $this->whereSQL = $where;
        return $this;
    }

    public function groupBy (string $groupBy)
    {
        $this->groupBySQL = $groupBy;
        return $this;
    }

    public function having (string $having)
    {
        $this->havingSQL = $having;
        return $this;
    }

    public function orderBy (string $orderBy)
    {
        $this->orderSQL = $orderBy;
        return $this;
    }

    public function bind (array $data)
    {
        $this->bindData = $data;
        return $this;
    }

    /* Журналирование/прослушка SQL запросов */
    public function listen ()
    {
        $this->listenSQL = true;
        return $this;
    }

    /* Получить все записи */
    public function get (): array
    {
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return [];}
        return $stmt->fetchAll();
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
    public function pluck (string $key = null, string $value = null): array
    {
        if ($key !== null) {
            if ($value === null) {
                $this->fieldSQL = $key;
            } else {
                $this->fieldSQL = $key . ', ' . $value;
            }
        }
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return [];}
        if ($key === null) {
            $rows = $stmt->fetchAll(\PDO::FETCH_NUM);
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $key = 0;
        } else {
            $rows = $stmt->fetchAll();
        }
        $result = [];
        if ($value === null) {
            foreach ($rows as $row) {$result[] = $row[$key];}
        } else {
            foreach ($rows as $row) {$result[$row[$key]] = $row[$value];}
        }
        return $result;
    }

    /* Получить значение первого столпца первой строки */
    public function getCell (string $fieldName = '')
    {
        if ($fieldName !== '') {$this->fieldSQL = $fieldName;}
        $sql = $this->getSQL();
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return null;}
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return $row[0];
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

    /*
        Пример использования:
    $data = ['id' => '22'];
    $update = ['name' => 'qqqq'];
    $affectedRows = $db->table('test')->where('id = :id')->bind($data)->update($update);
    */
    public function update (array $dataForUpdate): int
    {
        $keys = array_keys($dataForUpdate);
        $string = '';
        foreach ($keys as $key) {$string .= $key . ' = :' . $key . ', ';}
        $update_string = rtrim($string, ', ');
        $sql = 'UPDATE ' . $this->tableSQL . PHP_EOL . 'SET ' . $update_string . PHP_EOL . 'WHERE ' . $this->whereSQL;
        $stmt = $this->executeSQL($sql, array_merge($this->bindData, $dataForUpdate));
        if ($stmt === null) {return 0;}
        $this->affectedRows = $stmt->rowCount();
        return $this->affectedRows;
    }

    public function updateOrInsert (array $dataForUpdate): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->tableSQL . ' WHERE ' . $this->whereSQL;
        $stmt = $this->executeSQL($sql, $this->bindData);
        $rows = $stmt->fetch(\PDO::FETCH_NUM);
        $count = $rows[0];

        if ($count === 0) {
            $this->affectedRows = $this->insert(array_merge($this->bindData, $dataForUpdate));
        } else {
            $this->affectedRows = $this->update($dataForUpdate);
        }
        return $this->affectedRows;
    }

    public function delete (): int
    {
        $string = $this->parametersString($this->bindData);
        /** @noinspection SqlWithoutWhere */
        $sql = 'DELETE FROM ' . $this->tableSQL . PHP_EOL . 'WHERE ' . $string;
        $stmt = $this->executeSQL($sql, $this->bindData);
        if ($stmt === null) {return 0;}
        $this->affectedRows = $stmt->rowCount();
        return $this->affectedRows;
    }

    public function getAffectedRows ()
    {
        return $this->affectedRows;
    }

    public function getErrorsCount (): int
    {
        return $this->errors_count;
    }

    public function getLastInsertId ()
    {
        return $this->lastInsertId;
    }

    public function getNewUUID (): string
    {
        return $this->selectRaw('SELECT UUID()')->getCell();
    }

    public function getSQL (): string
    {
        if ($this->method === 'Raw') {return $this->sql;}

        $sql = 'SELECT ' . $this->fieldSQL . PHP_EOL . 'FROM ' . $this->tableSQL;
        if ($this->whereSQL !== null) {$sql .= PHP_EOL . 'WHERE ' . $this->whereSQL;}
        if ($this->groupBySQL !== null) {$sql .= PHP_EOL . 'GROUP BY ' . $this->groupBySQL;}
        if ($this->havingSQL !== null) {$sql .= PHP_EOL . 'HAVING ' . $this->havingSQL;}
        if ($this->orderSQL !== null) {$sql .= PHP_EOL . 'ORDER BY ' . $this->orderSQL;}

        return $sql;
    }

    public function getTimeExecution ()
    {
        return $this->sql_time;
    }

    public function beginTransaction (): void
    {
        $this->errors_before_transaction = $this->errors_count;
        $this->pdo->beginTransaction();
    }

    public function endTransaction (): void
    {
        if ($this->errors_before_transaction === $this->errors_count)
        {$this->pdo->commit();} else {$this->pdo->rollBack();}
    }

    public function commit (): void
    {
        $this->pdo->commit();
    }

    public function rollback (): void
    {
        $this->pdo->rollBack();
    }

    public function enableQueryLog (string $logName, bool $overwrite = false): void
    {
        $this->logName = $logName;
        $this->logOverwrite = $overwrite;
        $this->queryLog = true;
    }

    public function disableQueryLog (): void
    {
        $this->queryLog = false;
    }

    public function clearQueryLog (string $logName): void
    {
        file_put_contents(ROOT . '/logs/' . $logName . '.log', '');
    }

    protected function executeSQL (string $sql, array $data = [])
    {
        $stmt = null;
        $start_time = microtime(true);
        $this->sql_time = null;
        $this->lastInsertId = 0;
        $error_message = null;

        if ($this->listenSQL) {vd($sql, $data);}

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($data as $key => $value) {$stmt->bindValue($key, $value);}
            $stmt->execute();
            $this->sql_time = round(microtime(true) - $start_time, 4);
            if ($this->listenSQL) {vd('SQL time: ' . $this->sql_time);}
        } catch (\Exception $e) {
            $this->errors_count++;
            $error_message = $e->getMessage();
            if ($this->listenSQL) {echo $e->getMessage();}
            SQLerrorLog::save(debug_backtrace(), [$e->getMessage(), $sql, $data]);
        }

        if ($this->queryLog) {
            $log_params = ['sql' => $sql, 'data' => $data];
            if ($this->sql_time !== null) {$log_params['time'] = $this->sql_time;}
            if ($error_message !== null) {$log_params['message'] = $error_message;}

            SQLqueryLog::save($this->logName, $log_params);
        }

        return $stmt;
    }

    protected function insertData (array $data): int
    {
        $keys = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));
        $sql = 'INSERT INTO ' . $this->tableSQL . ' (' .$keys . ')' . PHP_EOL . 'VALUES (' . $values . ')';
        $stmt = $this->executeSQL($sql, $data);
        if ($stmt === null) {return 0;}
        $this->lastInsertId = $this->pdo->lastInsertId();
        return $stmt->rowCount();
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