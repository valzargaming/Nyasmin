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
 * Handles the API endpoints "Channel".
 * @internal
 */
class Channel {
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
    
    function getChannel(string $channelid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['get'], $channelid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function modifyChannel(string $channelid, array $data, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['modify'], $channelid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $data));
    }
    
    function deleteChannel(string $channelid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['delete'], $channelid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function getChannelMessages(string $channelid, array $options = array()) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['list'], $channelid);
        return $this->api->makeRequest('GET', $url, array('data' => $options));
    }
    
    function getChannelMessage(string $channelid, string $messageid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['get'], $channelid, $messageid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createMessage(string $channelid, array $options, array $files = array()) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['create'], $channelid);
        return $this->api->makeRequest('POST', $url, array('data' => $options, 'files' => $files));
    }
    
    function editMessage(string $channelid, string $messageid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['edit'], $channelid, $messageid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function deleteMessage(string $channelid, string $messageid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['delete'], $channelid, $messageid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function bulkDeleteMessages(string $channelid, array $snowflakes, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['bulkDelete'], $channelid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason, 'data' => array('messages' => $snowflakes)));
    }
    
    function createMessageReaction(string $channelid, string $messageid, string $emoji) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['reactions']['create'], $channelid, $messageid, $emoji);
        return $this->api->makeRequest('PUT', $url, array());
    }
    
    function deleteMessageReaction(string $channelid, string $messageid, string $emoji) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['reactions']['delete'], $channelid, $messageid, $emoji);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function deleteMessageUserReaction(string $channelid, string $messageid, string $emoji, string $userid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['reactions']['deleteUser'], $channelid, $messageid, $emoji, $userid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function getMessageReactions(string $channelid, string $messageid, string $emoji) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['reactions']['get'], $channelid, $messageid, $emoji);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function deleteMessageReactions(string $channelid, string $messageid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['reactions']['deleteAll'], $channelid, $messageid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function editChannelPermissions(string $channelid, string $overwriteid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['permissions']['edit'], $channelid, $overwriteid);
        return $this->api->makeRequest('PUT', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function deleteChannelPermission(string $channelid, string $overwriteid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['permissions']['delete'], $channelid, $overwriteid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function getChannelInvites(string $channelid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['invites']['list'], $channelid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createChannelInvite(string $channelid, array $options = array()) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['invites']['create'], $channelid);
        return $this->api->makeRequest('GET', $url, array('data' => $options));
    }
    
    function triggerChannelTyping(string $channelid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['typing'], $channelid);
        return $this->api->makeRequest('POST', $url, array());
    }
    
    function getPinnedChannelMessages(string $channelid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['pins']['list'], $channelid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function pinChannelMessage(string $channelid, string $messageid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['pins']['add'], $channelid, $messageid);
        return $this->api->makeRequest('PUT', $url, array());
    }
    
    function unpinChannelMessage(string $channelid, string $messageid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['pins']['delete'], $channelid, $messageid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function groupDMAddRecipient(string $channelid, string $userid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['groupDM']['add'], $channelid, $userid);
        return $this->api->makeRequest('PUT', $url, array());
    }
    
    function groupDMRemoveRecipient(string $channelid, string $userid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['groupDM']['remove'], $channelid, $userid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
}
