<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Emoji Storage to store emojis, utilizes Collection.
 */
class EmojiStorage extends Storage {
    protected $guild;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, ?\CharlotteDunois\Yasmin\Models\Guild $guild = null, ?array $data = null) {
        parent::__construct($client, $data);
        $this->guild = $guild;
    }
    
    /**
     * Resolves given data to an emoji.
     * @param \CharlotteDunois\Yasmin\Models\Emoji|\CharlotteDunois\Yasmin\Models\MessageReaction|string  string = emoji ID
     * @return \CharlotteDunois\Yasmin\Models\Emoji
     * @throws \InvalidArgumentException
     */
    function resolve($emoji) {
        if($emoji instanceof \CharlotteDunois\Yasmin\Models\Emoji) {
            return $emoji;
        }
        
        if($emoji instanceof \CharlotteDunois\Yasmin\Models\MessageReaction) {
            return $emoji->emoji;
        }
        
        if(\is_int($emoji)) {
            $emoji = (string) $emoji;
        }
        
        if(\is_string($emoji) && $this->has($emoji)) {
            return $this->get($emoji);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown emoji');
    }
    
    /**
     * @inheritDoc
     */
    function set($key, $value) {
        parent::set($key, $value);
        if($this !== $this->client->emojis) {
            $this->client->emojis->set($key, $value);
        }
        
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    function delete($key) {
        parent::delete($key);
        if($this !== $this->client->emojis) {
            $this->client->emojis->delete($key);
        }
        
        return $this;
    }
    
    /**
     * @internal
     */
    function factory(array $data) {
        $emoji = new \CharlotteDunois\Yasmin\Models\Emoji($this->client, $this->guild, $data);
        $id = ($emoji->id ?? $emoji->name);
        
        $this->set($id, $emoji);
        $this->client->emojis->set($id, $emoji);
            
        return $emoji;
    }
}
