<?php

namespace App\controllers;

use App\QueryBuilder;

class Controller
{
    protected $db;

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

}
