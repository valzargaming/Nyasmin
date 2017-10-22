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
 * Handles the API endpoints "Invite".
 * @access private
 */
class Invite {
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
    
    function getInvite(string $code) {
        $url = Constants::format(Constants::ENDPOINTS_INVITES['get'], $code);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function deleteInvite(string $code) {
        $url = Constants::format(Constants::ENDPOINTS_INVITES['delete'], $code);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function acceptInvite(string $code) {
        $url = Constants::format(Constants::ENDPOINTS_INVITES['accept'], $code);
        return $this->api->makeRequest('POST', $url, array());
    }
}
