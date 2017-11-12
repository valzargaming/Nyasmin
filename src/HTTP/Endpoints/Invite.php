<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP\Endpoints;

use \CharlotteDunois\Yasmin\Constants;

/**
 * Handles the API endpoints "Invite".
 * @internal
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
    
    function deleteInvite(string $code, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_INVITES['delete'], $code);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function acceptInvite(string $code) {
        $url = Constants::format(Constants::ENDPOINTS_INVITES['accept'], $code);
        return $this->api->makeRequest('POST', $url, array());
    }
}
