<?php

namespace modules\notifications\admin;

use m\core;
use m\module;
use m\view;
use m\registry;
use modules\notifications\models\notifications_types;

class types extends module {

    public function _init()
    {
        $types = notifications_types::call_static()
            ->s([], [[['site' => $this->site->id], ['site' => null]]], [1000])
            ->all('object');

        $items = [];

        if (!empty($types)) {
            foreach ($types as $type) {
                $items[] = $this->view->notifications_types_item->prepare($type);
            }
        }

        view::set_css($this->module_path . '/css/types_overview.css');


        view::set('content', $this->view->notifications_types_overview->prepare([
            'items' => implode("\n", $items),
        ]));

        unset($types);
        unset($items);
    }
}
