<?php

namespace modules\notifications\models;

use m\model;
use m\registry;

class notifications_types extends model
{
    protected $_sort = ['sequence' => 'ASC', 'id' => 'DESC'];

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'type' => 'varchar',
        'sequence' => 'int',
    ];

    public static function get_options_arr()
    {
        $options = [];

        $types_arr = self::call_static()
            ->s([], ['site' => registry::get('site')->id], [100])
            ->all();

        if (empty($types_arr)) {
            return $options;
        }

        foreach ($types_arr as $type) {
            $options[] = [
                'value' => $type['id'],
                'name' => $type['type'],
            ];
        }

        return $options;
    }
}
