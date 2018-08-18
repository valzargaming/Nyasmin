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
 * @see https://discordapp.com/developers/docs/topics/gateway#guild-member-update
 * @internal
 */
class GuildMemberUpdate implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $clones = $this->client->getOption('disableClones', array());
        $this->clones = !($clones === true || \in_array('guildMemberUpdate', (array) $clones));
    }
    
    function handle(array $data): void {
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
            } else {
                $guild->fetchMember($data['user']['id'])->done();
            }
        }
    }
}
