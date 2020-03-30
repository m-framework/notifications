<?php

namespace modules\notifications\models;

use m\model;
use m\registry;

class notifications_events extends model
{
    protected $_sort = ['sequence' => 'ASC', 'id' => 'DESC'];

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'event' => 'varchar',
        'name' => 'varchar',
        'active' => 'tinyint',
        'sequence' => 'int',
    ];

    public static function get_options_arr()
    {
        $options = [];

        $events = self::call_static()
            ->s([], ['site' => registry::get('site')->id], [100])
            ->all();

        if (empty($events)) {
            return $options;
        }

        foreach ($events as $event) {
            $options[] = [
                'value' => $event['id'],
                'name' => $event['name'],
            ];
        }

        return $options;
    }

    public function _autoload_active_checked()
    {
        $this->active_checked = empty($this->active) ? '' : 'checked';
    }
}
