<?php

namespace modules\notifications\client;

use m\core;
use m\i18n;
use m\m_mail;
use m\module;
use m\registry;
use m\view;
use modules\logs\models\logs;
use modules\notifications\models\notifications_events;
use modules\notifications\models\notifications_rules;
use modules\notifications\models\notifications_templates;
use modules\special_blocks\models;
use modules\users\models\users;

class notification extends module
{
    public function _init()
    {

    }

    public function notify($event_code, $event_object)
    {
        $event = notifications_events::call_static()
            ->s([], [[['site' => $this->site->id], ['site' => null]], 'event' => $event_code, 'active' => 1])
            ->obj();

        $event_cond = [['event_code' => $event_code]];

        if (!empty($event->id)) {
            $event_cond[] = ['event' => $event->id];
        }

        $disabled_notification = notifications_rules::call_static()
            ->s(['id'],
                [
                    [['site' => $this->site->id], ['site' => null]],
                    'user' => $this->user->profile,
                    'notify' => null,
                    $event_cond
                ]
            )
            ->obj();

        if (!empty($disabled_notification) && !empty($disabled_notification->id)) {
            logs::set($event_code . ' : user ' . $this->user->profile . ' : disallowed notification');
            return false;
        }

        /**
         * Send notifications to admin or managers (users that placed in templates table via admin panel)
         */
        $admin_templates = notifications_templates::call_static()
            ->s([],
                [
                    [['site' => $this->site->id], ['site' => null]],
                    [['language' => (int)$this->language_id], ['language' => null]],
                    'user' => ['not' => null],
                    $event_cond,
                    'active' => 1,
                ],
                [100]
            )
            ->all('object');
            
        $db_logs = (array)registry::get('db_logs');
        $last_log = end($db_logs);

        if (!empty($last_log)) {
            logs::set($last_log);
        }

        if (!empty($admin_templates)) {
            foreach ($admin_templates as $event_template) {
                $this->send($event_code, $event_template, $event_object);
            }
        }

        /**
         * Send notifications to client by default templates (w/o attached users)
         */
        if (empty($event->id)) {
            return true;
        }

        $client_templates = notifications_templates::call_static()
            ->s([],
                [
                    [['site' => $this->site->id], ['site' => null]],
                    [['language' => (int)$this->language_id], ['language' => null]],
                    'user' => null,
                    'event' => $event->id,
                    'active' => 1,
                ],
                [100]
            )
            ->all('object');

        logs::set(end(registry::get('db_logs')));


        if (!empty($client_templates)) {
            foreach ($client_templates as $event_template) {
                $this->send($event_code, $event_template, $event_object);
            }
        }
    }

    /**
     * Convert text from template in DB to dynamic_view and call replacing all variables (incl. via _autoload_ ).
     * So in templates texts can be used all variables from given model ($event_object).
     * Ask sending provider (Email, SMS, etc.) from template and send a message via this provider.
     *
     * @param $template
     * @param $event_object
     * @return bool
     */
    private function send($event_code, $template, $event_object)
    {
        $this->view->notification_template = htmlspecialchars_decode(stripslashes($template->template));
        $message = $this->view->notification_template->prepare($event_object);

        $user = null;

        if (!empty($template->user)) {
            $user = new users($template->user);
        }
        else if (!empty($event_object->user)) {
            $user = new users($event_object->user);
        }
        else if (!empty($this->user->profile)) {
            $user = $this->user;
        }

        if (empty($user)) {
            logs::set('Can\'t detect recipient : ' . $event_code . ' - ' . $template->id);
            return false;
        }

        $provider = $template->provider;

        if (empty($provider->sender)) {
            logs::set('Empty provider\'s sender: ' . $provider->name);
            return false;
        }

        $provider->sender = str_replace('~domain~', $this->site->host, $provider->sender);

        return $provider->send([
            'from' => $provider->sender,
            'to' => (int)$template->type == 1 ? $user->info->email : $user->info->phone,
            'recipient' => (int)$template->type == 1 ? $user->info->email : $user->info->phone,
            'subject' => i18n::get('Notification from') . ' ' . $this->site->host,
            'message' => $message,
        ]);
    }
}