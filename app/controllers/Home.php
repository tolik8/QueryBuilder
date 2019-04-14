<?php

namespace App\controllers;

class Home extends Controller
{
     public function index (): void
    {
        $sql = getSQL('country.sql');
        $countries = $this->db->select($sql, ['region' => 'Eastern Europe'])->get();
        vd($countries);
    }

}