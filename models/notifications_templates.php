<?php

namespace modules\notifications\models;

use m\model;
use m\registry;
use modules\users\models\users_info;

class notifications_templates extends model
{
    //public $_table = 'special_blocks';
//    protected $_sort = ['id' => 'ASC'];

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'language' => 'int',
        'user' => 'int',
        'type' => 'int',
        'event' => 'int', // client will select in personal cabinet needed events from specific table
        'event_code' => 'varchar', // additional event name for admin
        'expected_model' => 'varchar',
        'template' => 'text',
        'active' => 'tinyint',
    ];

    public function _autoload_type_name()
    {
        $this->type_name = notifications_types::call_static()
            ->s(['type'], ['id' => $this->type])->obj()->type;
    }

    public function _autoload_event_name_code()
    {
        $this->event_name_code = empty($this->event) ? $this->event_code : notifications_events::call_static()
            ->s(['event'], ['id' => $this->event])->obj()->event;
    }

    public function _autoload_active_checked()
    {
        $this->active_checked = empty($this->active) ? '' : 'checked';
    }

    public function _autoload_user_name()
    {
        $this->user_name = empty($this->user) ? '*' : users_info::call_static()
            ->s([], ['profile' => $this->user])
            ->obj()
            ->name;
    }

    public function _autoload_provider()
    {
        $this->provider = notifications_providers::call_static()
            ->s([],
                [
                    [['site' => registry::get('site')->id], ['site' => null]],
                    'type' => $this->type,
                    'default' => 1,
                ]
            )
            ->obj();
    }
}
