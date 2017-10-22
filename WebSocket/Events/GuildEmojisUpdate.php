<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\WebSocket\Events;

/**
 * WS Event
 * @link https://discordapp.com/developers/docs/topics/gateway#guild-emojis-update
 * @access private
 */
class GuildEmojisUpdate {
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    function handle(array $data) {
        $guild = $this->client->guilds->get($data['guild_id']);
        if($guild) {
            $ids = array();
            foreach($data['emojis'] as $emoji) {
                $ids[] = $emoji['id'];
                
                if($guild->emojis->has($emoji['id'])) {
                    $guild->emojis->get($emoji['id'])->_patch($emoji);
                } else {
                    $guild->emojis->set($emoji['id'], (new \CharlotteDunois\Yasmin\Structures\Emoji($this->client, $guild, $emoji)));
                }
            }
            
            foreach($guild->emojis->all() as $emoji) {
                if(!in_array($emoji['id'], $ids)) {
                    $guild->emojis->delete($emoji['id']);
                }
            }
            
            $this->client->emit('guildEmojisUpdate', $guild);
        }
    }
}
