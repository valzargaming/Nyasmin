<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
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
     * The manual creation of such an object is discouraged. There may be an easy and safe way to create such an object in the future.
     * @param \CharlotteDunois\Yasmin\Client  $client  The client this object is for.
     * @param array                           $game    An array containing name, type (as int) and url (nullable).
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $game) {
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
    
    /**
     * @access private
     */
    function jsonSerialize() {
        $type = \array_search($this->type, \CharlotteDunois\Yasmin\Constants::GAME_TYPES, true);
        return array(
            'name' => $this->name,
            'type' => (\is_int($type) ? $type : 0),
            'url' => $this->url
        );
    }
}
