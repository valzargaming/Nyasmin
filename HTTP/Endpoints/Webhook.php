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
 * Handles the API endpoints "Webhook".
 * @access private
 */
class Webhook {
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
    
    function createWebhook(string $channelid, string $nick, string $avatarBase64) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['create'], $channelid);
        return $this->api->makeRequest('POST', $url, array('data' => array('nick' => $nick, 'avatar' => $avatarBase64)));
    }
    
    function getChannelWebhooks(string $channelid) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['channels'], $channelid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getGuildsWebhooks(string $guildid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['guilds'], $guildid);
        return $this->api->makeRequest('GET', $url, array('auditLogReason' => $reason));
    }
    
    function getWebhook(string $webhookid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['get'], $webhookid);
        return $this->api->makeRequest('GET', $url, array('auditLogReason' => $reason));
    }
    
    function getWebhookToken(string $webhookid, string $token, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['getToken'], $webhookid, $token);
        return $this->api->makeRequest('GET', $url, array('auditLogReason' => $reason));
    }
    
    function modifyWebhook(string $webhookid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['modify'], $webhookid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function modifyWebhookToken(string $webhookid, string $token, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['modifyToken'], $webhookid, $token);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function deleteWebhook(string $webhookid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['delete'], $webhookid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function deleteWebhookToken(string $webhookid, string $token, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['deleteToken'], $webhookid, $token);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function executeWebhook(string $webhookid, string $token, array $options, array $files = array()) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['execute'], $webhookid, $token);
        return $this->api->makeRequest('POST', $url, array('data' => $options, 'files' => $files));
    }
}
