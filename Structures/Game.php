<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Game extends Structure { //TODO: Docs
    protected $name;
    protected $type;
    protected $url;
    
    function __construct($client, $game) {
        parent::__construct($client);
        
        $this->name = $game['name'];
        $this->type = \CharlotteDunois\Yasmin\Constants::GAME_TYPES[$game['type']];
        $this->url = (!empty($game['url']) ? $game['url'] : null);
    }
    
    function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return NULL;
    }
}
