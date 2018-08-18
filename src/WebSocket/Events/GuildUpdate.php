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
 * @see https://discordapp.com/developers/docs/topics/gateway#guild-update
 * @internal
 */
class GuildUpdate implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $clones = $this->client->getOption('disableClones', array());
        $this->clones = !($clones === true || \in_array('guildUpdate', (array) $clones));
    }
    
    function handle(array $data): void {
        $guild = $this->client->guilds->get($data['id']);
        if($guild) {
            if(($data['unavailable'] ?? false)) {
                $guild->_patch(array('unavailable' => true));
                $this->client->emit('guildUnavailable', $guild);
                return;
            }
            
            $oldGuild = null;
            if($this->clones) {
                $oldGuild = clone $guild;
            }
            
            $guild->_patch($data);
            
            $this->client->emit('guildUpdate', $guild, $oldGuild);
        }
    }
}
