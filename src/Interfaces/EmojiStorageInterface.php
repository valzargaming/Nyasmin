<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Interfaces;

/**
 * Something all emoji storages implement. The storage also is used as factory.
 */
interface EmojiStorageInterface extends StorageInterface {
    /**
     * Resolves given data to an emoji.
     * @param \CharlotteDunois\Yasmin\Models\Emoji|\CharlotteDunois\Yasmin\Models\MessageReaction|string|int  $emoji  string/int = emoji ID
     * @return \CharlotteDunois\Yasmin\Models\Emoji
     * @throws \InvalidArgumentException
     */
    function resolve($emoji);
    
    /**
     * Returns the item at a given key. If the key does not exist, null is returned.
     * @param string  $key
     * @return \CharlotteDunois\Yasmin\Models\Emoji|null
     * @throws \InvalidArgumentException
    */
    function get($key);
    
    /**
     * Sets a key-value pair.
     * @param string                                $key
     * @param \CharlotteDunois\Yasmin\Models\Emoji  $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    function set($key, $value);
    
    /**
     * Factory to create (or retrieve existing) emojis.
     * @param array  $data
     * @return \CharlotteDunois\Yasmin\Models\Emoji
     * @internal
     */
    function factory(array $data);
}
