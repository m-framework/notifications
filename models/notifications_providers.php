<?php

namespace modules\notifications\models;

use libraries\curl\simple_curl;
use libraries\simple_mail\simple_mail;
use m\core;
use m\m_mail;
use m\model;
use m\registry;
use modules\logs\models\logs;

class notifications_providers extends model
{
    protected $_sort = ['sequence' => 'ASC', 'id' => 'DESC'];

    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'type' => 'int',
        'name' => 'varchar',
        'sender' => 'varchar',
        'api_host' => 'varchar',
        'api_login' => 'varchar',
        'api_password' => 'varchar',
        'api_key' => 'varchar',
        'api_send_url' => 'text',
        'class_method' => 'varchar',
        'default' => 'tinyint',
        'sequence' => 'int',
    ];
    public function _before_save()
    {
        if (!empty($this->type)) {
            notifications_providers::call_static()
                ->u(['default' => null], ['site' => registry::get('site')->id, 'type' => $this->type, 'default' => 1]);
        }

        return true;
    }

    public function _before_destroy()
    {
        if (empty($this->default)) {
            $default = notifications_providers::call_static()
                ->s([], ['site' => registry::get('site')->id, 'type' => $this->type, 'default' => 1])
                ->obj();
        }
        else {
            return false;
        }

        if (empty($default) || empty($default->id)) {
            $first_of_type = notifications_providers::call_static()
                ->s([], ['site' => registry::get('site')->id, 'type' => $this->type, 'id' => ['not' => $this->id]])
                ->obj();

            if (!empty($first_of_type) && !empty($first_of_type->id)) {
                $first_of_type->save(['default' => 1]);
            }
            else {
                return false;
            }
        }

        return true;
    }

    public function _autoload_type_name()
    {
        $this->type_name = notifications_types::call_static()
            ->s(['type'], ['id' => $this->type])->obj()->type;
    }

    public function _autoload_default_checked()
    {
        $this->default_checked = empty($this->default) ? '' : 'checked';
    }

    public function send($arr)
    {
        if ((int)$this->type == 1) {
            if (empty($arr['from']) || empty($arr['to']) || empty($arr['subject']) || empty($arr['message'])) {
                logs::set('Can\'t send Email notification: empty important data');
                return false;
            }

            return simple_mail::send($arr['from'], $arr['to'], $arr['subject'], $arr['message']);
        }

        if (empty($arr['recipient']) || empty($arr['message'])) {
            logs::set('Can\'t send ' . $this->name . ' notification: empty important data');
            return false;
        }

        $gateway_url = htmlspecialchars_decode(htmlspecialchars_decode($this->api_send_url));

        $gateway_url_arr = [
            '~api_host~' => urlencode($this->api_host),
            '~api_login~' => $this->api_login,
            '~api_password~' => $this->api_password,
            '~api_key~' => $this->api_key,
            '~sender~' => urlencode($this->sender),
            '~recipient~' => urldecode($arr['recipient']),
            '~phone~' => preg_replace("/\D/", '', urldecode($arr['recipient'])),
            '~message~' => urlencode($arr['message']),
        ];

        $gateway_url = str_replace(array_keys($gateway_url_arr), array_values($gateway_url_arr), $gateway_url);

        $send = simple_curl::parse_content($gateway_url);

        logs::set('Send ' . $this->name . ' notification: ' . $send);

        return !empty($send);
    }
}
