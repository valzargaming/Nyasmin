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
 * Represents a message reaction.
 */
class MessageReaction extends Structure { //TODO: Implementation
    protected $message;
    
    protected $count;
    protected $me;
    protected $emoji;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Structures\Message $message, \CharlotteDunois\Yasmin\Structures\Emoji $emoji, array $reaction) { //TODO: Implementation
        parent::__construct($client);
        $this->message = $message;
        
        $this->count = (int) $reaction['count'];
        $this->me = (bool) $reaction['me'];
        $this->emoji = $emoji;
    }
    
    /**
     * @property-read int                                         $count     Times this emos emoji has been reacted.
     * @property-read bool                                        $me        Whether the current user has reacted using this emoji.
     *
     * @property-read \CharlotteDunois\Yasmin\Structures\Message  $message   The message this reaction belongs to.
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return null;
    }
    
    /**
     * Increments the count.
     * @access private
     */
    function _incrementCount() {
        $this->count++;
    }
    
    /**
     * Decrements the count.
     * @access private
     */
    function _decrementCount() {
        $this->count--;
    }
}
