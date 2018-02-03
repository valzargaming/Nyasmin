<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @see https://discordapp.com/developers/docs/topics/gateway#guild-delete
 * @internal
 */
class GuildDelete implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['id']);
        if($guild) {
            if($guild->available && ($data['unavailable'] ?? false)) {
                $guild->_patch(array('unavailable' => true));
                $this->client->emit('guildUnavailable', $guild);
            } else {
                $this->client->guilds->delete($guild->id);
                $this->client->emit('guildDelete', $guild);
            }
        }
    }
}
