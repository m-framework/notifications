<?php

namespace modules\notifications\admin;

use m\config;
use m\core;
use m\form;
use m\i18n;
use m\module;
use m\view;
use m\registry;
use modules\notifications\models\notifications_events;
use modules\notifications\models\notifications_rules;
use modules\notifications\models\notifications_types;
use modules\users\models\users_info;

class rules extends module {

    public function _init()
    {
        if ($this->alias == 'add' || (!empty($this->get->rules) && $this->get->rules == 'edit' && !empty($this->get->id))) {
            return $this->edit();
        }

        if (!empty($this->get->rules) && $this->get->rules == 'delete' && !empty($this->get->id)) {
            return $this->delete();
        }

        if (!isset($this->view->notifications_rules_overview) || !isset($this->view->notifications_rules_item)) {
            return false;
        }

        $rules = notifications_rules::call_static()
            ->s([], [[['site' => $this->site->id], ['site' => null]]], [1000])
            ->all('object');

        $items = [];

        if (!empty($rules)) {
            foreach ($rules as $rule) {
                $items[] = $this->view->notifications_rules_item->prepare($rule);
            }
        }

        view::set_css($this->module_path . '/css/rules_overview.css');

        $alerts = '';

        if (!empty($_SESSION['rule_delete'])) {
            $alerts .= $this->view->div_success->prepare(['text' => '*Rule successfully deleted*']);
            unset($_SESSION['rule_delete']);
        }


        $this->js = ['/js/onchange_update.js'];

        view::set('content', $this->view->notifications_rules_overview->prepare([
            'items' => implode("\n", $items),
            'alerts' => $alerts,
        ]));

        unset($types);
        unset($items);
    }

    private function edit()
    {
        if (!isset($this->view->notifications_rule_form)) {
            return false;
        }

        $rule = new notifications_rules(empty($this->get->id) ? null : $this->get->id);

        if (!empty($rule->id)) {
            view::set('page_title', '<h1><i class="fa fa-list-alt"></i> *Edit a notification rule* ' .
                (empty($rule->event_name) ? '' : '`' . $rule->event_name . '`') . '</h1>');
            registry::set('title', i18n::get('Edit a notification rule'));

            registry::set('breadcrumbs', [
                '/' . config::get('admin_panel_alias') . '/notifications' => '*Notifications*',
                '/' . config::get('admin_panel_alias') . '/notifications/rules' => '*Rules*',
                '/' . config::get('admin_panel_alias') . '/notifications/rules/edit/id/' . $rule->id => '*Edit a notification rule*',
            ]);
        }
        else {
            view::set('page_title', '<h1><i class="fa fa-list-alt"></i> *Add new notification rule*</h1>');
            registry::set('title', i18n::get('Add new notification rule'));

            registry::set('breadcrumbs', [
                '/' . config::get('admin_panel_alias') . '/notifications' => '*Notifications*',
                '/' . config::get('admin_panel_alias') . '/notifications/rules' => '*Rules*',
                '/' . config::get('admin_panel_alias') . '/notifications/rules/add' => '*Add new notification rule*',
            ]);
        }

        if (empty($rule->site)) {
            $rule->site = $this->site->id;
        }



        new form(
            $rule,
            [
                'user' => [
                    'field_name' => i18n::get('User'),
                    'related' => users_info::call_static()->s(['profile as value', "CONCAT(first_name,' ',last_name) as name"],[],10000)->all(),
                ],
                'event' => [
                    'field_name' => i18n::get('Notification event'),
                    'related' => notifications_events::get_options_arr(),
                ],
                'event_code' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Or custom event name'),
                ],
                'type' => [
                    'field_name' => i18n::get('Notification type'),
                    'related' => notifications_types::get_options_arr(),
                    'required' => 1,
                ],
                'notify' => [
                    'type' => 'tinyint',
                    'field_name' => i18n::get('Notify'),
                ],
                'site' => [
                    'type' => 'hidden',
                    'field_name' => '',
                ],
            ],
            [
                'form' => $this->view->notifications_rule_form,
                'varchar' => $this->view->edit_row_varchar,
                'related' => $this->view->edit_row_related,
                'hidden' => $this->view->edit_row_hidden,
                'tinyint' => $this->view->edit_row_tinyint,
                'saved' => $this->view->edit_row_saved,
                'error' => $this->view->edit_row_error,
            ]
        );
    }

    private function delete()
    {
        $notifications_rule = new notifications_rules($this->get->id);
        if (!empty($notifications_rule->id) && $this->user->is_admin() && $notifications_rule->destroy()) {
            $_SESSION['rule_delete'] = 1;
        }
        core::redirect('/' . $this->conf->admin_panel_alias . '/notifications/rules');
        return true;
    }
}
