<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @see https://discordapp.com/developers/docs/topics/gateway#guild-member-update
 * @access private
 */
class GuildMemberUpdate {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        
        $clones = (array) $this->client->getOption('disableClones', array());
        $this->clones = !\in_array('guildMemberUpdate', $clones);
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $guildmember = $guild->members->get($data['user']['id']);
            if($guildmember) {
                $oldMember = null;
                if($this->clones) {
                    $oldMember = clone $guildmember;
                }
                
                $guildmember->_patch($data);
                $this->client->emit('guildMemberUpdate', $guildmember, $oldMember);
            }
        }
    }
}
