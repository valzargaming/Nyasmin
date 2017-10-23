<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\HTTP\Endpoints;

use \CharlotteDunois\Yasmin\Constants;

/**
 * Handles the API endpoints "Voice".
 * @access private
 */
class Voice {
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     */
    protected $api;
    
    /**
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager $api
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api) {
        $this->api = $api;
    }
    
    function listVoiceRegions() {
        $url = Constants::format(Constants::ENDPOINTS_VOICE['regions']);
        return $this->api->makeRequest('GET', $url, array());
    }
}
