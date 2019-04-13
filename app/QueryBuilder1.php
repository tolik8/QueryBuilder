<?php

namespace App;

class QueryBuilder1
{
    protected $pdo;
    protected $errors_before_transaction;
    public $sql_time = 0;
    public $sql_times = [];
    public $sql_count = 0;
    public $errors_count = 0;
    public $columns = [];
    public $resultIsOk;

    public function __construct (\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll ($tables, array $data = [], $sort = ''): array
    {
        $sql = 'SELECT * FROM ' . $tables . CR;
        $string = $this->ParametersString($data);
        if (!empty($data)) {$sql .= 'WHERE ' . $string . CR;}
        if ($sort !== '') {$sql .= 'ORDER BY ' . $sort;}

        return $this->getAllFromSQL($sql, $data);
    }

    public function getAllFromSQL ($sql, array $data = []): array
    {
        $start_time = $this->beforeExecute();

        $stmt = $this->execute($sql, $data);
        if ($stmt->errorCode() !== '00000') {return [];}

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($rows)) {$this->columns = array_keys($rows[0]);}
        else {$this->columns = []; return [];}

        if ($this->resultIsOk) {$this->sql_time = round(microtime(true) - $start_time, 4);}

        return $rows;
    }

    public function getOneValue ($field, $table, array $data = [])
    {
        $sql = 'SELECT ' . $field . CR . 'FROM ' . $table . CR;
        $string = $this->ParametersString($data);
        if (!empty($data)) {$sql .= 'WHERE ' . $string;}

        return $this->getOneValueFromSQL($sql, $data);
    }

    public function getOneValueFromSQL ($sql, array $data = [])
    {
        $start_time = $this->beforeExecute();

        $stmt = $this->execute($sql, $data);
        if ($stmt->errorCode() !== '00000') {return null;}

        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if (empty($row)) {return null;}

        $result = $row[0];

        if ($this->resultIsOk) {$this->sql_time = round(microtime(true) - $start_time, 4);}

        return $result;
    }

    public function getOneRow ($table, array $data = [], $fields = '*'): array
    {
        $sql = 'SELECT ' . $fields . ' FROM ' . $table . CR;
        $string = $this->ParametersString($data);
        if (!empty($data)) {$sql .= 'WHERE ' . $string;}

        return $this->getOneRowFromSQL($sql, $data);
    }

    public function getOneRowFromSQL ($sql, array $data = []): array
    {
        $start_time = $this->beforeExecute();

        $stmt = $this->execute($sql, $data);
        if ($stmt->errorCode() !== '00000') {return [];}

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (empty($row)) {return [];}

        if ($this->resultIsOk) {$this->sql_time = round(microtime(true) - $start_time, 4);}

        return $row;
    }

    public function getOneCol ($fields, $tables, array $data = []): array
    {
        $sql = 'SELECT ' . $fields . CR . 'FROM ' . $tables . CR;
        $string = $this->ParametersString($data);
        if (!empty($data)) {$sql .= 'WHERE ' . $string;}

        return $this->getOneColFromSQL($sql, $data);
    }

    public function getOneColFromSQL ($sql, array $data = []): array
    {
        $start_time = $this->beforeExecute();

        $stmt = $this->execute($sql, $data);
        if ($stmt->errorCode() !== '00000') {return [];}

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) {return [];}

        $result = [];
        foreach ($rows as $row) {foreach ($row as $value) {$result[] = $value;}}

        if ($this->resultIsOk) {$this->sql_time = round(microtime(true) - $start_time, 4);}

        return $result;
    }

    // ��������� ������ � ������� ������ ������� ��� ����, � ������ ��������
    public function getKeyValue ($fields, $tables, array $data = [], $sort = ''): array
    {
        $sql = 'SELECT ' . $fields . ' FROM ' . $tables . CR;
        if ($sort !== '') {$sql .= 'ORDER BY ' . $sort;}
        $string = $this->ParametersString($data);
        if (!empty($data)) {$sql .= ' WHERE ' . $string;}

        return $this->getKeyValueFromSQL($sql, $data);
    }

    public function getKeyValueFromSQL ($sql, array $data = []): array
    {
        $start_time = $this->beforeExecute();

        $stmt = $this->execute($sql, $data);
        if ($stmt->errorCode() !== '00000') {return [];}

        $rows = $stmt->fetchAll(\PDO::FETCH_NUM);
        if (empty($rows)) {return [];}

        $result = [];
        foreach ($rows as $row) {$result[$row[0]] = $row[1];}

        if ($this->resultIsOk) {$this->sql_time = round(microtime(true) - $start_time, 4);}

        return $result;
    }

    // ��������� ������ � ������� ������ ������� ��� ����, � �������� ������������� ������
    public function getKeyValues ($fields, $tables, array $data = []): array
    {
        $sql = "SELECT {$fields} FROM {$tables}";
        $string = $this->ParametersString($data);
        if (!empty($data)) {$sql .= ' WHERE ' . $string;}

        return $this->getKeyValuesFromSQL($sql, $data);
    }

    public function getKeyValuesFromSQL ($sql, array $data = []): array
    {
        $start_time = $this->beforeExecute();

        $stmt = $this->execute($sql, $data);
        if ($stmt->errorCode() !== '00000') {return [];}

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) {return [];}

        $result = $rows2 = $fields = [];
        foreach ($rows[0] as $key => $value) {$fields[] = $key;}
        foreach ($rows as $row) {$rows2[] = array_values($row);}
        $col_count = $stmt->columnCount();
        foreach ($rows2 as $row) {
            for ($i = 2; $i <= $col_count; $i++) {
                $column_name = $fields[$i-1];
                $result[$row[0]][$column_name] = $row[$i-1];
            }
        }

        if ($this->resultIsOk) {$this->sql_time = round(microtime(true) - $start_time, 4);}

        return $result;
    }

    public function insert ($table, array $data): int
    {
        $keys = implode(', ', array_keys($data));
        $tags = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$keys}) VALUES ({$tags})";

        return $this->runSQL($sql, $data);
    }

    public function update ($table, array $update, array $where): int
    {
        $keys = array_keys($update);
        $string = '';
        foreach ($keys as $key) {$string .= $key . ' = :' . $key . ', ';}
        $keys = rtrim($string, ', ');
        $data = array_merge($update, $where);
        $where_string = $this->ParametersString($where);
        $sql = "UPDATE {$table} SET {$keys} WHERE {$where_string}";

        return $this->runSQL($sql, $data);
    }

    public function delete ($table, array $data): int
    {
        $string = $this->ParametersString($data);
        /** @noinspection SqlWithoutWhere */
        $sql = "DELETE FROM {$table} WHERE {$string}";

        return $this->runSQL($sql, $data);
    }

    public function getCount ($table, array $data = []): int
    {
        $sql = 'SELECT COUNT(*) cnt FROM ' . $table . CR;
        $string = $this->ParametersString($data);
        if (!empty($data)) {$sql .= 'WHERE ' . $string;}

        return $this->getOneValueFromSQL($sql, $data);
    }

    public function runSQL ($sql, array $data = []): int
    {
        $start_time = $this->beforeExecute();

        $stmt = $this->execute($sql, $data);
        if ($stmt->errorCode() !== '00000') {return 0;}

        $result = (int) $stmt->rowCount();

        if ($this->resultIsOk) {$this->sql_time = round(microtime(true) - $start_time, 4);}

        return $result;
    }

    public function getNewGUID (): string
    {
        $start_time = $this->beforeExecute();

        $sql = 'SELECT sys_guid() FROM dual';
        $stmt = $this->execute($sql);
        if ($stmt->errorCode() !== '00000') {return '';}

        $row = $stmt->fetch(\PDO::FETCH_NUM);
        $result = $row[0];

        if ($this->resultIsOk) {$this->sql_time = round(microtime(true) - $start_time, 4);}

        return $result;
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

    protected function beforeExecute ()
    {
        $this->sql_time = 0;
        $this->sql_count++;
        $this->resultIsOk = true;

        return microtime(true);
    }

    protected function execute ($sql, array $data = [])
    {
        $stmt = null;
        $start_time = microtime(true);

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($data as $key => $value) {$stmt->bindValue(':' . $key, $value);}
            $stmt->execute();
            $this->sql_times[] = round(microtime(true) - $start_time, 4);
        } catch (\Exception $e) {
            $this->resultIsOk = false;
            $this->errors_count++;
            Log::save(debug_backtrace(), [$e->getMessage(), $sql, $data]);
        }

        return $stmt;
    }

    protected function ParametersString (array $data): string
    {
        $string = '';
        $keys = array_keys($data);

        foreach ($keys as $key) {$string .= $key . ' = :' . $key . ' AND ';}
        $string = rtrim($string, ' AND ');

        return $string;
    }

}