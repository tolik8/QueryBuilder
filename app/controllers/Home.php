<?php

namespace App\controllers;

class Home extends Controller
{
     public function index (): void
    {
        $db = $this->db;

        /*$sql = getSQL('country.sql');
        $countries = $this->db->select($sql, ['region' => 'Eastern Europe'])->get();
        vd($countries);*/

        /*$data = [
            ['ID' => '23', 'NAME' => 'test2'],
            ['ID' => '24', 'NAME' => 'test3'],
        ];
        $affectedRows = $db->table('test')->insert($data);
        vd($affectedRows);*/

        /*$data = ['ID' => '24'];
        $affectedRows = $db->table('test')->delete($data);
        vd($affectedRows);*/

        $update = ['NAME' => 'qqq'];
        $where = ['ID' => '22'];
        $affectedRows = $db->table('test')->update($update, $where);
        vd($affectedRows);
    }

}