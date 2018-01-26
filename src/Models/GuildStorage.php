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
 * Guild Storage to store guilds, utilizes Collection.
 */
class GuildStorage extends Storage {
    /**
     * Resolves given data to a guild.
     * @param \CharlotteDunois\Yasmin\Models\Guild|string  $guild  string = guild ID
     * @return \CharlotteDunois\Yasmin\Models\Guild
     * @throws \InvalidArgumentException
     */
    function resolve($guild) {
        if($guild instanceof \CharlotteDunois\Yasmin\Models\Guild) {
            return $guild;
        }
        
        if(\is_int($guild)) {
            $guild = (string) $guild;
        }
        
        if(\is_string($guild) && $this->has($guild)) {
            return $this->get($guild);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown guild');
    }
    
    /**
     * @internal
     */
    function factory(array $data) {
        if($this->has($data['id'])) {
            $guild = $this->get($data['id']);
            $guild->_patch($data);
            return $guild;
        }
        
        $guild = new \CharlotteDunois\Yasmin\Models\Guild($this->client, $data);
        $this->set($guild->id, $guild);
        return $guild;
    }
}
