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
 * Guild Storage to store guilds, utilizes Collection.
 * @todo Docs
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
        
        if(\is_string($guild) && $this->has($guild)) {
            return $this->get($guild);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown guild');
    }
}
