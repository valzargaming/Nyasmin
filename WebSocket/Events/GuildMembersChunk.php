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
 * @link https://discordapp.com/developers/docs/topics/gateway#guild-members-chunk
 * @access private
 */
class GuildMembersChunk {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $members = new \CharlotteDunois\Yasmin\Structures\Collection();
            foreach($data['members'] as $member) {
                $member = $guild->members->factory($member);
                $members->set($member->id, $member);
            }
            
            $this->client->emit('guildMembersChunk', $guild, $members);
        }
    }
}
