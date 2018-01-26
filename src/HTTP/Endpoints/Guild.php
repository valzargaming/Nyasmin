<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP\Endpoints;

use \CharlotteDunois\Yasmin\Constants;

/**
 * Handles the API endpoints "Guild".
 * @internal
 */
class Guild {
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
    
    function getGuild(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['get'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuild(array $options) {
        $url = Constants::ENDPOINTS_GUILDS['create'];
        return $this->api->makeRequest('POST', $url, array('data' => $options));
    }
    
    function modifyGuild(string $guildid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['modify'], $guildid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function deleteGuild(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['delete'], $guildid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function getGuildChannels(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['channels']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildChannel(string $guildid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['channels']['create'], $guildid);
        return $this->api->makeRequest('POST', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function modifyGuildChannelPositions(string $guildid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['channels']['modifyPositions'], $guildid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function getGuildMember(string $guildid, string $userid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['get'], $guildid, $userid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function listGuildMembers(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function addGuildMember(string $guildid, string $userid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['add'], $guildid, $userid);
        return $this->api->makeRequest('PUT', $url, array('data' => $options));
    }
    
    function modifyGuildMember(string $guildid, string $userid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['modify'], $guildid, $userid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function removeGuildMember(string $guildid, string $userid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['remove'], $guildid, $userid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function modifyCurrentNick(string $guildid, string $userid, string $nick) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['modifyCurrentNick'], $guildid, $userid);
        return $this->api->makeRequest('PATCH', $url, array('data' => array('nick' => $nick)));
    }
    
    function addGuildMemberRole(string $guildid, string $userid, string $roleid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['addRole'], $guildid, $userid, $roleid);
        return $this->api->makeRequest('PUT', $url, array('auditLogReason' => $reason));
    }
    
    function removeGuildMemberRole(string $guildid, string $userid, string $roleid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['removeRole'], $guildid, $userid, $roleid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function getGuildBans(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['bans']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildBan(string $guildid, string $userid, int $daysDeleteMessages = 0, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['bans']['create'], $guildid, $userid);
        
        $qs = array('delete-message-days' => $daysDeleteMessages);
        if(!empty($reason)) {
            $qs['reason'] = $reason;
        }
        
        return $this->api->makeRequest('PUT', $url, array('auditLogReason' => $reason, 'querystring' => $qs));
    }
    
    function removeGuildBan(string $guildid, string $userid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['bans']['remove'], $guildid, $userid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function getGuildRoles(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildRole(string $guildid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['create'], $guildid);
        return $this->api->makeRequest('POST', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function modifyGuildRolePositions(string $guildid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['modifyPositions'], $guildid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function modifyGuildRole(string $guildid, string $roleid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['modify'], $guildid, $roleid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function deleteGuildRole(string $guildid, string $roleid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['delete'], $guildid, $roleid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function getGuildPruneCount(string $guildid, int $days) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['prune']['count'], $guildid);
        return $this->api->makeRequest('GET', $url, array('querystring' => array('days' => $days)));
    }
    
    function beginGuildPrune(string $guildid, int $days, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['prune']['begin'], $guildid);
        return $this->api->makeRequest('POST', $url, array('auditLogReason' => $reason, 'querystring' => array('days' => $days)));
    }
    
    function getGuildVoiceRegions(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['voice']['regions'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getGuildInvites(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['invites']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getGuildIntegrations(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['integrations']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildIntegration(string $guildid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['integrations']['create'], $guildid);
        return $this->api->makeRequest('POST', $url, array('data' => $options));
    }
    
    function modifyGuildIntegration(string $guildid, string $integrationid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['integrations']['modify'], $guildid, $integrationid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function deleteGuildIntegration(string $guildid, string $integrationid, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['integrations']['delete'], $guildid, $integrationid);
        return $this->api->makeRequest('DELETE', $url, array('auditLogReason' => $reason));
    }
    
    function syncGuildIntegration(string $guildid, string $integrationid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['integrations']['sync'], $guildid, $integrationid);
        return $this->api->makeRequest('POST', $url, array());
    }
    
    function getGuildEmbed(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['embed']['get'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function modifyGuildEmbed(string $guildid, array $options, string $reason = '') {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['embed']['modify'], $guildid);
        return $this->api->makeRequest('PATCH', $url, array('auditLogReason' => $reason, 'data' => $options));
    }
    
    function getGuildAuditLog(string $guildid, array $query) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['audit-logs'], $guildid);
        return $this->api->makeRequest('GET', $url, array('querystring' => $query));
    }
}
