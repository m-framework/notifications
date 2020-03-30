<?php

namespace modules\notifications\admin;

use m\config;
use m\core;
use m\form;
use m\i18n;
use m\module;
use m\view;
use m\registry;
use modules\notifications\models\notifications_providers;
use modules\notifications\models\notifications_types;

class providers extends module {

    public function _init()
    {
        if ($this->alias == 'add' || (!empty($this->get->providers) && $this->get->providers == 'edit' && !empty($this->get->id))) {
            return $this->edit();
        }

        if (!empty($this->get->providers) && $this->get->providers == 'delete' && !empty($this->get->id)) {
            return $this->delete();
        }

        $providers = notifications_providers::call_static()
            ->s([], [[['site' => $this->site->id], ['site' => null]]], [1000])
            ->all('object');

        $items = [];

        if (!empty($providers)) {
            foreach ($providers as $provider) {
                $items[] = empty($provider->default) ? $this->view->notifications_providers_item->prepare($provider)
                    : $this->view->notifications_providers_item_default->prepare($provider);
            }
        }

        view::set_css($this->module_path . '/css/providers_overview.css');

        $this->js = ['/js/onchange_update.js'];

        $alerts = '';

        if (!empty($_SESSION['provider_delete'])) {
            $alerts .= $this->view->div_success->prepare(['text' => '*Provider successfully deleted*']);
            unset($_SESSION['provider_delete']);
        }

        view::set('content', $this->view->notifications_providers_overview->prepare([
            'items' => implode("\n", $items),
            'alerts' => $alerts,
        ]));

        unset($types);
        unset($items);
    }

    private function edit()
    {
        if (!isset($this->view->notifications_provider_form)) {
            return false;
        }

        $provider = new notifications_providers(empty($this->get->id) ? null : $this->get->id);

        if (!empty($provider->id)) {
            view::set('page_title', '<h1><i class="fa fa-list-alt"></i> *Edit a notification provider* ' .
                (empty($provider->name) ? '' : '`' . $provider->name . '`') . '</h1>');
            registry::set('title', i18n::get('Edit a notification provider'));

            registry::set('breadcrumbs', [
                '/' . config::get('admin_panel_alias') . '/notifications' => '*Notifications*',
                '/' . config::get('admin_panel_alias') . '/notifications/providers' => '*Providers*',
                '/' . config::get('admin_panel_alias') . '/notifications/providers/edit/id/' . $provider->id => '*Edit a notification provider*',
            ]);
        }
        else {
            view::set('page_title', '<h1><i class="fa fa-list-alt"></i> *Add new notification provider*</h1>');
            registry::set('title', i18n::get('Add new notification provider'));

            registry::set('breadcrumbs', [
                '/' . config::get('admin_panel_alias') . '/notifications' => '*Notifications*',
                '/' . config::get('admin_panel_alias') . '/notifications/providers' => '*Providers*',
                '/' . config::get('admin_panel_alias') . '/notifications/providers/add' => '*Add new notification provider*',
            ]);
        }

        if (empty($provider->site)) {
            $provider->site = $this->site->id;
        }



        new form(
            $provider,
            [
                'type' => [
                    'field_name' => i18n::get('Notification type'),
                    'related' => notifications_types::get_options_arr(),
                    'required' => 1,
                ],
                'name' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Name'),
                    'required' => 1,
                ],
                'sender' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Notifications sender'),
                    'required' => 1,
                ],
                'api_host' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('API host'),
                ],
                'api_login' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('API login'),
                ],
                'api_key' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('API key'),
                ],
                'api_send_url' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('API send url'),
                ],
                'class_method' => [
                    'type' => 'varchar',
                    'field_name' => i18n::get('Or local class and method (static)'),
                ],
                'default' => [
                    'type' => 'tinyint',
                    'field_name' => i18n::get('Use as default'),
                ],
                'site' => [
                    'type' => 'hidden',
                    'field_name' => '',
                ],
            ],
            [
                'form' => $this->view->notifications_provider_form,
                'varchar' => $this->view->edit_row_varchar,
                'text' => $this->view->edit_row_text,
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
        $notifications_provider = new notifications_providers($this->get->id);
        if (!empty($notifications_provider->id) && $this->user->is_admin() && $notifications_provider->destroy()) {
            $_SESSION['provider_delete'] = 1;
        }
        core::redirect('/' . $this->conf->admin_panel_alias . '/notifications/providers');
        return true;
    }
}
