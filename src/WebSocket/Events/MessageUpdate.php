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
 * @see https://discordapp.com/developers/docs/topics/gateway#message-update
 * @internal
 */
class MessageUpdate implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    protected $clones = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
        $this->client = $client;
        
        $clones = $this->client->getOption('disableClones', array());
        $this->clones = !($clones === true || \in_array('messageUpdate', (array) $clones));
    }
    
    function handle(\CharlotteDunois\Yasmin\WebSocket\WSConnection $ws, array $data): void {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $message = $channel->messages->get($data['id']);
            if($message) {
                // Minor bug in Discord - Event gets emitted when a message gets updated (not edited!) when additional data is available (e.g. image dimensions)
                $edited = ($message->editedTimestamp === ($data['edited_timestamp'] ?? null) || (new \DateTime(($data['edited_timestamp'] ?? 'now')))->getTimestamp() !== $message->editedTimestamp);
                
                $oldMessage = null;
                if($this->clones) {
                    $oldMessage = clone $message;
                }
                
                $message->_patch($data);
                
                if($edited) {
                    $this->client->emit('messageUpdate', $message, $oldMessage);
                }
            } else {
                $this->client->emit('messageUpdateRaw', $channel, $data);
            }
        }
    }
}
