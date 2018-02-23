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
 * @see https://discordapp.com/developers/docs/topics/gateway#message-reaction-remove
 * @internal
 */
class MessageReactionRemove implements \CharlotteDunois\Yasmin\Interfaces\WSEventInterface {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\WebSocket\WSManager $wsmanager) {
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
            
            $message->done(function ($message) use ($data) {
                $id = (!empty($data['emoji']['id']) ? $data['emoji']['id'] : $data['emoji']['name']);
                
                $reaction = $message->reactions->get($id);
                if($reaction) {
                    $reaction->_decrementCount();
                    
                    if($this->client->users->has($data['user_id'])) {
                        $user = \React\Promise\resolve($this->client->users->get($data['user_id']));
                    } else {
                        $user = $this->client->fetchUser($data['user_id']);
                    }
                    
                    $user->done(function ($user) use ($message, $reaction) {
                        $reaction->users->delete($user->id);
                        if($reaction->count === 0) {
                            $message->reactions->delete(($reaction->emoji->id ?? $reaction->emoji->name));
                        }
                        
                        $this->client->emit('messageReactionRemove', $reaction, $user);
                    }, array($this->client, 'handlePromiseRejection'));
                }
            }, function () {
                // Don't handle it
            });
        }
    }
}
