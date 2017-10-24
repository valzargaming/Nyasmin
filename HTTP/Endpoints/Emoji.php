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
 * Handles the API endpoints "Emoji".
 * @access private
 */
class Emoji {
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
    
    function listGuildEmojis(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getGuildEmoji(string $guildid, string $emojiid) {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['get'], $guildid, $emojiid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildEmoji(string $guildid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['create'], $guildid);
        return $this->api->makeRequest('POST', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function modifyGuildEmoji(string $guildid, string $emojiid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['modify'], $guildid, $emojiid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function deleteGuildEmoji(string $guildid, string $emojiid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['delete'], $guildid, $emojiid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
}
