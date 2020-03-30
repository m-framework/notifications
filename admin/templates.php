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
use modules\notifications\models\notifications_templates;
use modules\notifications\models\notifications_types;
use modules\users\models\users_info;

class templates extends module {

    public function _init()
    {
        if ($this->alias == 'add' || (!empty($this->get->templates) && $this->get->templates == 'edit' && !empty($this->get->id))) {
            return $this->edit();
        }

        if (!empty($this->get->templates) && $this->get->templates == 'delete' && !empty($this->get->id)) {
            return $this->delete();
        }

        $templates = notifications_templates::call_static()
            ->s([], [[['site' => $this->site->id], ['site' => null]]], [1000])
            ->all('object');

        $items = [];

        if (!empty($templates)) {
            foreach ($templates as $template) {
                $items[] = $this->view->notifications_templates_item->prepare($template);
            }
        }

        view::set_css($this->module_path . '/css/templates_overview.css');

        $this->js = ['/js/onchange_update.js'];

        $alerts = '';

        if (!empty($_SESSION['template_delete'])) {
            $alerts .= $this->view->div_success->prepare(['text' => '*Template successfully deleted*']);
            unset($_SESSION['template_delete']);
        }

        view::set('content', $this->view->notifications_templates_overview->prepare([
            'items' => implode("\n", $items),
            'alerts' => $alerts,
        ]));

        unset($types);
        unset($items);
    }

    private function edit()
    {
        if (!isset($this->view->notifications_template_form)) {
            return false;
        }

        $template = new notifications_templates(empty($this->get->id) ? null : $this->get->id);

        if (!empty($template->id)) {
            view::set('page_title', '<h1><i class="fa fa-list-alt"></i> *Edit a notification template* ' .
                (empty($template->name) ? '' : '`' . $template->name . '`') . '</h1>');
            registry::set('title', i18n::get('Edit a notification template'));

            registry::set('breadcrumbs', [
                '/' . config::get('admin_panel_alias') . '/notifications' => '*Notifications*',
                '/' . config::get('admin_panel_alias') . '/notifications/templates' => '*Templates*',
                '/' . config::get('admin_panel_alias') . '/notifications/templates/edit/id/' . $template->id => '*Edit a notification template*',
            ]);
        }
        else {
            view::set('page_title', '<h1><i class="fa fa-list-alt"></i> *Add new notification template*</h1>');
            registry::set('title', i18n::get('Add new notification template'));

            registry::set('breadcrumbs', [
                '/' . config::get('admin_panel_alias') . '/notifications' => '*Notifications*',
                '/' . config::get('admin_panel_alias') . '/notifications/templates' => '*Templates*',
                '/' . config::get('admin_panel_alias') . '/notifications/templates/add' => '*Add new notification template*',
            ]);
        }

        if (empty($template->site)) {
            $template->site = $this->site->id;
        }

        if (empty($template->language)) {
            $template->language = (string)$this->language_id;
        }


        new form(
            $template,
            [
                'type' => [
                    'field_name' => i18n::get('Notification type'),
                    'related' => notifications_types::get_options_arr(),
                    'required' => 1,
                ],
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
                'expected_model' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Expected event model class'),
                ],
                'template' => [
                    'type' => !empty($template->type) && $template->type == 1 ? 'text' : 'textarea',
                    'field_name' => i18n::get('Template text'),
                    'required' => 1,
                ],
                'active' => [
                    'type' => 'tinyint',
                    'field_name' => i18n::get('Active'),
                ],
                'language' => [
                    'type' => 'hidden',
                    'field_name' => '',
                ],
                'site' => [
                    'type' => 'hidden',
                    'field_name' => '',
                ],
            ],
            [
                'form' => $this->view->notifications_template_form,
                'varchar' => $this->view->edit_row_varchar,
                'text' => $this->view->edit_row_text,
                'textarea' => $this->view->edit_row_textarea,
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
        $notifications_template = new notifications_templates($this->get->id);
        if (!empty($notifications_template->id) && $this->user->is_admin() && $notifications_template->destroy()) {
            $_SESSION['template_delete'] = 1;
        }
        core::redirect('/' . $this->conf->admin_panel_alias . '/notifications/templates');
        return true;
    }
}
