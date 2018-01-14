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
 * @see https://discordapp.com/developers/docs/topics/gateway#guild-members-chunk
 * @internal
 */
class GuildMembersChunk {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $members = new \CharlotteDunois\Yasmin\Utils\Collection();
            foreach($data['members'] as $member) {
                $member = $guild->members->factory($member);
                $members->set($member->id, $member);
            }
            
            $this->client->emit('guildMembersChunk', $guild, $members);
        }
    }
}
