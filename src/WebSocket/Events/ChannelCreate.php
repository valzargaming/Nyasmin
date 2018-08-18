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
 * @see https://discordapp.com/developers/docs/topics/gateway#channel-create
 * @internal
 */
class ChannelCreate implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
    }
    
    function handle(array $data): void {
        $channel = $this->client->channels->factory($data);
        
        $prom = array();
        if($channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface) {
            foreach($channel->permissionOverwrites as $overwrite) {
                if($overwrite->type === 'member' && $overwrite->target === null) {
                    $prom[] = $channel->guild->fetchMember($overwrite->id)->then(function (\CharlotteDunois\Yasmin\Models\GuildMember $member) use ($overwrite) {
                        $overwrite->_patch(array('target' => $member));
                    }, function () {
                        // Do nothing
                    });
                }
            }
        }
        
        \React\Promise\all($prom)->done(function () use ($channel) {
            $this->client->emit('channelCreate', $channel);
        }, array($this->client, 'handlePromiseRejection'));
    }
}
