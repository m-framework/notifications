<?php

namespace modules\notifications\models;

use m\model;
use modules\users\models\users_info;

class notifications_rules extends model
{
    protected $_sort = ['sequence' => 'ASC', 'id' => 'DESC'];

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'user' => 'int',
        'event' => 'int',
        'event_code' => 'varchar', // separate events names for admin and client
        'type' => 'int',
        'notify' => 'tinyint',
        'sequence' => 'int',
    ];

    public function _autoload_type_name()
    {
        $this->type_name = notifications_types::call_static()
            ->s(['type'], ['id' => $this->type])->obj()->type;
    }

    public function _autoload_notify_checked()
    {
        $this->notify_checked = empty($this->notify) ? '' : 'checked';
    }

    public function _autoload_event_name_code()
    {
        $this->event_name_code = empty($this->event) ? $this->event_code : notifications_events::call_static()
            ->s(['type'], ['id' => $this->event])->obj()->event;
    }

    public function _autoload_user_name()
    {
        $this->user_name = empty($this->user) ? '*' : users_info::call_static()
            ->s([], ['profile' => $this->user])
            ->obj()
            ->name;
    }
}
