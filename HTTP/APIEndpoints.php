<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\HTTP;

use \CharlotteDunois\Yasmin\Constants;

/**
 * Handles the API.
 * @access private
 */
class APIEndpoints {
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     */
    protected $api;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\Endpoints\Channel
     */
    public $channel;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\Endpoints\Emoji
     */
    public $emoji;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\Endpoints\Guild
     */
    public $guild;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\Endpoints\Invite
     */
    public $invite;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\Endpoints\User
     */
    public $user;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\Endpoints\Voice
     */
    public $voice;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\Endpoints\Webhook
     */
    public $webhook;
    
    
    /**
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager $api
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api) {
        $this->api = $api;
        
        $this->channel = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Channel($api);
        $this->emoji = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Emoji($api);
        $this->guild = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Guild($api);
        $this->invite = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Invite($api);
        $this->user = new \CharlotteDunois\Yasmin\HTTP\Endpoints\User($api);
        $this->voice = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Voice($api);
        $this->webhook = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Webhook($api);
    }
    
    /**
     * @access private
     */
    function __call($name, $arguments) {
        if(method_exists($this->channel, $name)) {
            return $this->channel->$name(...$arguments);
        }
        
        if(method_exists($this->emoji, $name)) {
            return $this->emoji->$name(...$arguments);
        }
        
        if(method_exists($this->guild, $name)) {
            return $this->guild->$name(...$arguments);
        }
        
        if(method_exists($this->invite, $name)) {
            return $this->invite->$name(...$arguments);
        }
        
        if(method_exists($this->user, $name)) {
            return $this->user->$name(...$arguments);
        }
        
        if(method_exists($this->voice, $name)) {
            return $this->voice->$name(...$arguments);
        }
        
        if(method_exists($this->webhook, $name)) {
            return $this->webhook->$name(...$arguments);
        }
        
        throw new \Exception('API Endpoints method "'.$name.'" does not exist.');
    }
}
