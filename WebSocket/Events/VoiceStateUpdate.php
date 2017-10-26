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
 * @link https://discordapp.com/developers/docs/topics/gateway#voice-state-update
 * @access private
 */
class VoiceStateUpdate {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $user = $this->client->users->get($data['user_id']);
        if($user) {
            $channel = $this->client->channels->get($data['channel_id']);
            
            $oldVoice = null;
            $voice = $this->client->voiceStates->get($user->id);
            
            if($voice) {
                $oldVoice = clone $voice;
                $voice->_patch($data);
                $voice->_updateChannel($channel);
            } else {
                $voice = new \CharlotteDunois\Yasmin\Structures\VoiceState($this->client, $channel, $data);
                
                $this->client->voiceStates->set($user->id, $voice);
                if($channel->guild) {
                    $channel->guild->voiceStates->set($user->id, $voice);
                }
            }
            
            $this->client->emit('voiceStateUpdate', $voice, $oldVoice);
        }
    }
}
