<?php

namespace modules\notifications\admin;

use m\core;
use m\module;
use m\view;
use m\registry;
use modules\notifications\models\notifications_events;
use modules\notifications\models\notifications_types;

class events extends module {

    public function _init()
    {
        if (!isset($this->view->notifications_events_overview) || !isset($this->view->notifications_events_item)) {
            return false;
        }

        $types = notifications_events::call_static()
            ->s([], [[['site' => $this->site->id], ['site' => null]]], [1000])
            ->all('object');

        $items = [];

        if (!empty($types)) {
            foreach ($types as $type) {
                $items[] = $this->view->notifications_events_item->prepare($type);
            }
        }

        view::set_css($this->module_path . '/css/events_overview.css');

        $this->js = ['/js/onchange_update.js'];

        view::set('content', $this->view->notifications_events_overview->prepare([
            'items' => implode("\n", $items),
        ]));

        unset($types);
        unset($items);
    }
}
