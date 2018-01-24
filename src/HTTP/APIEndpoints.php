<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP;

/**
 * Handles the API endpoints.
 * @internal
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
     * DO NOT initialize this class yourself.
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
    
    function getCurrentApplication() {
        $url = \CharlotteDunois\Yasmin\Constants::ENDPOINTS_GENERAL['currentOAuthApplication'];
        return $this->api->makeRequest('GET', $url, array());
    }
}
