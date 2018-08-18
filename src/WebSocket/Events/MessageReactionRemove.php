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
    
    function handle(array $data): void {
        $channel = $this->client->channels->get($data['channel_id']);
        if($channel) {
            $id = (!empty($data['emoji']['id']) ? $data['emoji']['id'] : $data['emoji']['name']);
            
            $message = $channel->messages->get($data['message_id']);
            $reaction = null;
            
            if($message) {
                $reaction = $message->reactions->get($id);
                if($reaction !== null) {
                    $reaction->_decrementCount();
                    
                    if($reaction->users->has($data['user_id'])) {
                        $reaction->_patch(array('me' => false));
                    }
                }
                
                $message = \React\Promise\resolve($message);
            } else {
                $message = $channel->fetchMessage($data['message_id']);
            }
            
            $message->done(function (\CharlotteDunois\Yasmin\Models\Message $message) use ($data, $channel, $id, $reaction) {
                if(!$reaction) {
                    $reaction = $message->reactions->get($id);
                    if(!$reaction) {
                        $emoji = $this->client->emojis->get($id);
                        if(!$emoji) {
                            $emoji = new \CharlotteDunois\Yasmin\Models\Emoji($this->client, ($channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface ? $channel->guild : null), $data['emoji']);
                            if($channel instanceof \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface) {
                                $channel->guild->emojis->set($id, $emoji);
                            }
                            
                            $this->client->emojis->set($id, $emoji);
                        }
                        
                        $reaction = new \CharlotteDunois\Yasmin\Models\MessageReaction($this->client, $message, $emoji, array(
                            'count' => 0,
                            'me' => false,
                            'emoji' => $emoji
                        ));
                    }
                }
                
                $this->client->fetchUser($data['user_id'])->done(function (\CharlotteDunois\Yasmin\Models\User $user) use ($message, $reaction) {
                    $reaction->users->delete($user->id);
                    if($reaction->count === 0) {
                        $message->reactions->delete(($reaction->emoji->id ?? $reaction->emoji->name));
                    }
                    
                    $this->client->emit('messageReactionRemove', $reaction, $user);
                }, array($this->client, 'handlePromiseRejection'));
            }, function () {
                // Don't handle it
            });
        }
    }
}
