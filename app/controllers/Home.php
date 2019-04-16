<?php

namespace App\controllers;

class Home extends Controller
{
     public function index (): void
    {
        $db = $this->db;

        // Включить логирование запросов
        $db->enableQueryLog('queries');
        $db->clearQueryLog('queries');

        // Для прослушки одного запроса используйте метод listen
        //$db->table('country')->listen()->get();
        // Используется для отладки, покажет на странице SQL, параметры, время выполнения или ошибку

        // Также для отладки можно использувать getSQL
        //echo $db->table('country')->getSQL();

        echo '<hr>Методом "selectRaw" получить все записи "get"<br>';
        $data = ['region' => 'Eastern Europe'];
        $sql = getSQL('country.sql');
        $countries = $db->selectRaw($sql, $data)->get();

        foreach ($countries as $row) {
            foreach ($row as $item) {
                echo $item . ' ';
            }
            echo '<br>';
        }

        echo '<hr>Конструктором "table" получить все записи "get"<br>';
        $data = ['region' => 'Eastern Europe'];
        $countries = $db->table('country')->select('code, name')
            ->where('region = :region')->bind($data)->get();

        foreach ($countries as $row) {
            foreach ($row as $item) {
                echo $item . ' ';
            }
            echo '<br>';
        }

        echo '<hr>Получить значение первой строки первой колонки методом "getCell"<br>';
        $countRecords = $db->table('country')->select('COUNT(*)')->getCell();
        vd($countRecords);

        echo '<hr>Получить первую запись из таблицы методом "first"<br>';
        $country = $db->table('country')->select('code, name, region')->first();
        vd($country);

        echo '<hr>Получить одну колонку из таблицы методом "pluck"<br>';
        $data = ['region' => 'Eastern Europe'];
        $codes = $db->table('country')->where('region = :region')
            ->bind($data)->pluck('code');
        vd($codes);

        echo '<hr>Получить ассофиативный массив из таблицы методом "pluck"<br>';
        $data = ['region' => 'Eastern Europe'];
        $codes = $db->table('country')->where('region = :region')
            ->bind($data)->pluck('code', 'name');
        vd($codes);

        echo '<hr>Создать таблицу методом "statement"<br>';
        $result = $db->statement('CREATE TABLE tmp_test_table (id INT (11), name VARCHAR (250))');
        if ($result === '00000') {
            echo 'Таблица tmp_test_table создана.<br>';
        } else if ($result === '42S01') {
            echo 'Таблица tmp_test_table не создана, потому что уже существует.<br>';
        } else {
            echo 'Ошибка PDO ' . $result . '. Таблица tmp_test_table не создана.<br>';
        }

        // Очистить таблицу полностью
        $db->statement('TRUNCATE TABLE tmp_test_table');

        echo '<hr>Вставка данных методом "insert"<br>';
        $data = [
            ['id' => 1, 'name' => 'test1'],
            ['id' => 2, 'name' => 'test2'],
            ['id' => 3, 'name' => 'test3'],
            ['id' => 4, 'name' => 'test4'],
            ['id' => 5, 'name' => 'test5'],
        ];
        $affectedRows = $db->table('tmp_test_table')->insert($data);
        echo 'Вставлено записей ' . $affectedRows . '<br>';

        echo '<hr>Обновление данных методом "update"<br>';
        $data = ['id' => '4'];
        $update = ['name' => 'updated'];
        $affectedRows = $db->table('tmp_test_table')->where('id = :id')
            ->bind($data)->update($update);
        echo 'Обновлено записей ' . $affectedRows . '<br>';

        echo '<hr>Обновление или вставка данных методом "updateOrInsert"<br>';
        $data = ['id' => '6'];
        $update = ['name' => 'test6'];
        $affectedRows = $db->table('tmp_test_table')->where('id = :id')
            ->bind($data)->updateOrInsert($update);
        echo 'Обновлено записей ' . $affectedRows . '<br>';

        echo '<hr>Удаление данных методом "delete"<br>';
        $data = ['id' => '2'];
        $affectedRows = $db->table('tmp_test_table')->where('id = :id')
            ->bind($data)->delete();
        echo 'Удалено записей ' . $affectedRows . '<br>';

        // Полный список методов в файле app\QueryBuilder.php
    }

}