<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class GuildChannel extends Structure { //TODO: Implementation
    static function factory($client, $guild, $channel) {
        switch($channel) {
            case 0:
                return new \CharlotteDunois\Yasmin\Structures\TextChannel($client, $guild, $channel);
            break;
            case 1:
                throw new \Exception('A channel of type "DM" can not be a guild channel');
            break;
            case 2:
                return new \CharlotteDunois\Yasmin\Structures\VoiceChannel($client, $guild, $channel);
            break;
            case 3:
                throw new \Exception('A channel of type "Group DM" can not be a guild channel');
            break;
            case 4:
                return new \CharlotteDunois\Yasmin\Structures\ChannelCategory($client, $guild, $channel);
            break;
        }
    }
}
