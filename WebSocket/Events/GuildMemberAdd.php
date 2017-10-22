<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @link https://discordapp.com/developers/docs/topics/gateway#guild-member-add
 * @access private
 */
class GuildMemberAdd {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $guildmember = $guild->_addMember($data);
            $this->client->emit('guildMemberAdd', $guildmember);
        }
    }
}
