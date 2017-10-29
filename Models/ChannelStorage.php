<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * @internal
 */
class ChannelStorage extends \CharlotteDunois\Yasmin\Utils\Collection
    implements \CharlotteDunois\Yasmin\Interfaces\StorageInterface { //TODO: Docs
    
    protected $client;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $data = null) {
        parent::__construct($data);
        $this->client = $client;
    }
    
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
        }
        
        return null;
    }
    
    function resolve($channel) {
        if($channel instanceof \CharlotteDunois\Yasmin\Interfaces\ChannelInterface) {
            return $channel;
        }
        
        if(\is_string($channel) && $this->has($channel)) {
            return $this->get($channel);
        }
        
        throw new \InvalidArgumentException('Unable to resolve unknown channel');
    }
    
    function set($key, $value) {
        parent::set($key, $value);
        if($this !== $this->client->channels) {
            $this->client->channels->set($key, $value);
        }
        
        return $this;
    }
    
    function delete($key) {
        parent::delete($key);
        if($this !== $this->client->channels) {
            $this->client->channels->delete($key);
        }
        
        return $this;
    }
    
    function factory(array $data) {
        $guild = (!empty($data['guild_id']) ? $this->client->guilds->get($data['guild_id']) : null);
        
        switch($data['type']) {
            default:
                throw new \InvalidArgumentException('Unknown channel type');
            break;
            case 0:
                $channel = new \CharlotteDunois\Yasmin\Models\TextChannel($this->client, $guild, $data);
            break;
            case 1:
                $channel = new \CharlotteDunois\Yasmin\Models\DMChannel($this->client, $data);
            break;
            case 2:
                $channel = new \CharlotteDunois\Yasmin\Models\VoiceChannel($this->client, $guild, $data);
            break;
            case 3:
                $channel = new \CharlotteDunois\Yasmin\Models\GroupDMChannel($this->client, $data);
            break;
            case 4:
                $channel = new \CharlotteDunois\Yasmin\Models\CategoryChannel($this->client, $guild, $data);
            break;
        }
        
        $this->set($channel->id, $channel);
        
        if($guild) {
            $guild->channels->set($channel->id, $channel);
        }
        
        return $channel;
    }
}
