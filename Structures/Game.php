<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

/**
 * Something someone plays.
 */
class Game extends Structure {
    protected $name;
    protected $type;
    protected $url;
    
    /**
     * @access private
     */
    function __construct($client, $game) {
        parent::__construct($client);
        
        $this->name = $game['name'];
        $this->type = \CharlotteDunois\Yasmin\Constants::GAME_TYPES[$game['type']];
        $this->url = (!empty($game['url']) ? $game['url'] : null);
    }
    
    /**
     * @property-read string       $name  The name of the game.
     * @property-read string       $type  The type. Either Playing or Streaming.
     * @property-read string|null  $url   The stream url, if streaming.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return null;
    }
}
