<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin;

class EventEmitter extends \League\Event\Emitter { //TODO: Docs
    function on($name, $listener) {
        return $this->addListener($name, $listener);
    }
    
    function once($name, $listener) {
        return $this->addOneTimeListener($name, $listener);
    }
    
    function emit($name, ...$args) {
        $event = new \CharlotteDunois\Yasmin\Event($name, ...$args);
        $event->setEmitter($this);
        return parent::emit($event);
    }
}
