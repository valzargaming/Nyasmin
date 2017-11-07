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
 * @internal
 * @todo Docs
 */
class EmojiStorage extends Storage {
    protected $guild;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild = null, array $data = null) {
        parent::__construct($client, $data);
        $this->guild = $guild;
    }
    
    function resolve($emoji) {
        if($emoji instanceof \CharlotteDunois\Yasmin\Models\Emoji) {
            return $emoji;
        }
        
        if($emoji instanceof \CharlotteDunois\Yasmin\Models\MessageReaction) {
            return $emoji->emoji;
        }
        
        if(\is_string($emoji) && $this->has($emoji)) {
            return $this->get($emoji);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown emoji');
    }
    
    function factory(array $data) {
        $emoji = new \CharlotteDunois\Yasmin\Models\Emoji($this->client, $this->guild, $data);
        $id = ($emoji->id ?? $emoji->name);
        
        $this->set($id, $emoji);
        $this->client->emojis($id, $emoji);
            
        return $emoji;
    }
}
