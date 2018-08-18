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
 * @see https://discordapp.com/developers/docs/topics/gateway#channel-delete
 * @internal
 */
class ChannelDelete implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
    }
    
    function handle(array $data): void {
        $channel = $this->client->channels->get($data['id']);
        if($channel) {
            if($channel->guild) {
                $channel->guild->channels->delete($channel->id);
            }
            
            $this->client->channels->delete($channel->id);
            $this->client->emit('channelDelete', $channel);
        }
    }
}
