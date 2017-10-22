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
     * @var array
     */
    protected $endpoints = array();
    
    /**
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager $api
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api) {
        $this->api = $api;
        
        $this->endpoints['channel'] = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Channel($api);
        $this->endpoints['emoji'] = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Emoji($api);
        $this->endpoints['guild'] = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Guild($api);
        $this->endpoints['invite'] = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Invite($api);
        $this->endpoints['user'] = new \CharlotteDunois\Yasmin\HTTP\Endpoints\User($api);
        $this->endpoints['voice'] = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Voice($api);
        $this->endpoints['webhook'] = new \CharlotteDunois\Yasmin\HTTP\Endpoints\Webhook($api);
    }
    
    function __call($name, $arguments) {
        foreach($this->endpoints as $endpoints) {
            if(method_exists($endpoints, $name)) {
                return $endpoints->$name(...$arguments);
            }
        }
        
        throw new \Exception('API Endpoints method "'.$name.'" does not exist.');
    }
}
