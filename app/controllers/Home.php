<?php

namespace App\controllers;

class Home extends Controller
{
     public function index (): void
    {
        $db = $this->db;

        /*$sql = getSQL('country.sql');
        $countries = $this->db->execute($sql, ['region' => 'Eastern Europe'])->get();
        vd($countries);*/

        /*$data = [
            ['ID' => '23', 'NAME' => 'test2'],
            ['ID' => '24', 'NAME' => 'test3'],
        ];
        $affectedRows = $db->table('test')->insert($data);
        vd($affectedRows);
        vd('LastInsertId ' . $db->getLastInsertId());*/

        /*$data = ['ID' => '24'];
        $affectedRows = $db->table('test')->delete($data);
        vd($affectedRows);*/

        /*$where = ['id' => '22'];
        $update = ['name' => 'qqqq'];
        $affectedRows = $db->table('test')->where('id = :id')
            ->bind(array_merge($where, $update))->update($update);
        vd($affectedRows);*/

        /*$data = ['region' => 'Eastern Europe'];
        $countries = $db->table('country')->where('region = :region')
            ->bind($data)->pluck('code', 'name');
        vd($countries);

        $data = ['region' => 'North America'];
        $sql = 'SELECT name FROM country WHERE region = :region AND population > 100000 ORDER BY Name';
        $result = $db->execute($sql)->bind($data)->pluck('name');
        vd($result);*/

        $where = ['id' => '22'];
        $update = ['name' => 'a3', 'email' => 'test2@gmail.com'];
        $affectedRows = $db->table('test')->where('id = :id')->listen()
            ->bind(array_merge($where, $update))->updateOrInsert($update);
        vd($affectedRows);
    }

}