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
 * @see https://discordapp.com/developers/docs/topics/gateway#message-reaction-add
 * @internal
 */
class MessageReactionAdd {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $message = $channel->messages->get($data['message_id']);
            if($message) {
                $message = \React\Promise\resolve($message);
            } else {
                $message = $channel->fetchMessage($data['message_id']);
            }
            
            $message->then(function ($message) use ($data) {
                $reaction = $message->_addReaction($data);
                
                if($this->client->users->has($data['user_id'])) {
                    $user = \React\Promise\resolve($this->client->users->get($data['user_id']));
                } else {
                    $user = $this->client->fetchUser($data['user_id']);
                }
                
                $user->then(function ($user) use ($reaction) {
                    $reaction->users->set($user->id, $user);
                    
                    $this->client->emit('messageReactionAdd', $reaction, $user);
                })->done(null, array($this->client, 'handlePromiseRejection'));
            }, function () {
                // Don't handle it
            })->done(null, array($this->client, 'handlePromiseRejection'));
        }
    }
}
