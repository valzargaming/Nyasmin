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
 * Handles the API endpoints "User".
 * @access private
 */
class User {
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
    
    function getCurrentUser() {
        $url = Constants::format(Constants::ENDPOINTS_USERS['current']['get']);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getUser(string $userid) {
        $url = Constants::format(Constants::ENDPOINTS_USERS['get'], $userid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function modifyCurrentUser(array $options) {
        $url = Constants::format(Constants::ENDPOINTS_USERS['current']['modify']);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function getCurrentUserGuilds() {
        $url = Constants::format(Constants::ENDPOINTS_USERS['current']['guilds']);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function leaveCurrentUserGuild(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_USERS['current']['leaveGuild'], $guildid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function getUserDMs() {
        $url = Constants::format(Constants::ENDPOINTS_USERS['current']['dms']);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createUserDM(string $recipientid) {
        $url = Constants::format(Constants::ENDPOINTS_USERS['current']['createDM']);
        return $this->api->makeRequest('POST', $url, array('data' => array('recipient_id' => $recipientid)));
    }
    
    function createGroupDM(array $accessTokens, array $nicks) {
        $url = Constants::format(Constants::ENDPOINTS_USERS['current']['createGroupDM']);
        return $this->api->makeRequest('POST', $url, array('data' => array('access_tokens' => $accessTokens, 'nicks' => $nicks)));
    }
    
    function getUserConnections() {
        $url = Constants::format(Constants::ENDPOINTS_USERS['current']['connections']);
        return $this->api->makeRequest('GET', $url, array());
    }
}
