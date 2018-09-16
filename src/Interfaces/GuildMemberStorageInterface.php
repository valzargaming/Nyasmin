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
 * Something all guild member storages implement. The storage also is used as factory.
 */
interface GuildMemberStorageInterface extends StorageInterface {
    /**
     * Resolves given data to a guildmember.
     * @param \CharlotteDunois\Yasmin\Models\GuildMember|\CharlotteDunois\Yasmin\Models\User|string|int  $guildmember  string/int = user ID
     * @return \CharlotteDunois\Yasmin\Models\GuildMember
     * @throws \InvalidArgumentException
     */
    function resolve($guildmember);
    
    /**
     * Returns the item at a given key. If the key does not exist, null is returned.
     * @param string  $key
     * @return \CharlotteDunois\Yasmin\Models\GuildMember|null
     * @throws \InvalidArgumentException
    */
    function get($key);
    
    /**
     * Sets a key-value pair.
     * @param string                                      $key
     * @param \CharlotteDunois\Yasmin\Models\GuildMember  $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    function set($key, $value);
    
    /**
     * Factory to create (or retrieve existing) guild members.
     * @param array  $data
     * @return \CharlotteDunois\Yasmin\Models\GuildMember
     * @internal
     */
    function factory(array $data);
}
