<?php

namespace modules\notifications\models;

use m\model;

class notifications_shedule extends model
{
    //public $_table = 'special_blocks';
    protected $_sort = ['sequence' => 'ASC', 'id' => 'DESC'];

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'users' => 'varchar',
        'date' => 'date',
        'time' => 'time',
        'name' => 'varchar',
        'template' => 'int',
        'active' => 'tinyint',
        'sequence' => 'int',
    ];
}
