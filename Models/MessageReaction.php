<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Represents a message reaction.
 */
class MessageReaction extends ClientBase { //TODO: Implementation
    protected $message;
    
    protected $count;
    protected $me;
    protected $emoji;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Message $message, \CharlotteDunois\Yasmin\Models\Emoji $emoji, array $reaction) { //TODO: Implementation
        parent::__construct($client);
        $this->message = $message;
        
        $this->count = (int) $reaction['count'];
        $this->me = (bool) $reaction['me'];
        $this->emoji = $emoji;
    }
    
    /**
     * @property-read int                                         $count     Times this emos emoji has been reacted.
     * @property-read bool                                        $me        Whether the current user has reacted using this emoji.
     * @property-read \CharlotteDunois\Yasmin\Models\Message      $message   The message this reaction belongs to.
     *
     * @throws \Exception
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
    
    /**
     * Increments the count.
     * @internal
     */
    function _incrementCount() {
        $this->count++;
    }
    
    /**
     * Decrements the count.
     * @internal
     */
    function _decrementCount() {
        $this->count--;
    }
}
