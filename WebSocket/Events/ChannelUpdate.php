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
 * @see https://discordapp.com/developers/docs/topics/gateway#channel-update
 * @access private
 */
class ChannelUpdate {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        
        $clones = (array) $this->client->getOption('disableClones', array());
        $this->clones = !\in_array('channelUpdate', $clones);
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['id']);
        if($channel) {
            $oldChannel = null;
            if($this->clones) {
                $oldChannel = clone $channel;
            }
            
            $channel->_patch($data);
            
            $this->client->emit('channelUpdate', $channel, $oldChannel);
        }
    }
}
