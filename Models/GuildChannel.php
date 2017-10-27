<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

class GuildChannel extends ClientBase { //TODO: Implementation
    static function factory(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Models\Guild $guild, array $channel) {
        switch($channel['type']) {
            case 0:
                return (new \CharlotteDunois\Yasmin\Models\TextChannel($client, $guild, $channel));
            break;
            case 1:
                throw new \InvalidArgumentException('A channel of type "DM" can not be a guild channel');
            break;
            case 2:
                return (new \CharlotteDunois\Yasmin\Models\VoiceChannel($client, $guild, $channel));
            break;
            case 3:
                throw new \InvalidArgumentException('A channel of type "Group DM" can not be a guild channel');
            break;
            case 4:
                return (new \CharlotteDunois\Yasmin\Models\CategoryChannel($client, $guild, $channel));
            break;
        }
    }
}
