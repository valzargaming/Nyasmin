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
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager $api
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api) {
        $this->api = $api;
    }
    
    // Channel
    
    function getChannel(string $channelid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['get'], $channelid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function modifyChannel(string $channelid, array $data) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['modify'], $channelid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $data));
    }
    
    function deleteChannel(string $channelid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['delete'], $channelid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function getChannelMessages(string $channelid, array $options = array()) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['list'], $channelid);
        return $this->api->makeRequest('GET', $url, array('data' => $data));
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
    
    function deleteMessage(string $channelid, string $messageid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['delete'], $channelid, $messageid);
        return $this->api->makeRequest('DELETE', $url);
    }
    
    function bulkDeleteMessages(string $channelid, array $snowflakes) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['messages']['bulkDelete'], $channelid);
        return $this->api->makeRequest('DELETE', $url, array('data' => array('messages' => $snowflakes)));
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
    
    function editChannelPermissions(string $channelid, string $overwriteid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['permissions']['edit'], $channelid, $overwriteid);
        return $this->api->makeRequest('PUT', $url, array('data' => $options));
    }
    
    function deleteChannelPermission(string $channelid, string $overwriteid) {
        $url = Constants::format(Constants::ENDPOINTS_CHANNELS['permissions']['delete'], $channelid, $overwriteid);
        return $this->api->makeRequest('DELETE', $url, array());
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
    
    // Emoji
    
    function listGuildEmojis(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getGuildEmoji(string $guildid, string $emojiid) {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['get'], $guildid, $emojiid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildEmoji(string $guildid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['create'], $guildid);
        return $this->api->makeRequest('POST', $url, array('data' => $options));
    }
    
    function modifyGuildEmoji(string $guildid, string $emojiid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['modify'], $guildid, $emojiid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function deleteGuildEmoji(string $guildid, string $emojiid) {
        $url = Constants::format(Constants::ENDPOINTS_EMOJIS['delete'], $guildid, $emojiid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    // Guild
    
    function getGuild(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['get'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function modifyGuild(string $guildid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['modify'], $guildid);
        return $this->api->makeRequest('POST', $url, array('data' => $options));
    }
    
    function deleteGuild(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['delete'], $guildid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function getGuildChannels(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['channels']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildChannel(string $guildid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['channels']['create'], $guildid);
        return $this->api->makeRequest('POST', $url, array('data' => $options));
    }
    
    function modifyGuildChannelPositions(string $guildid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['channels']['modifyPositions'], $guildid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
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
    
    function modifyGuildMember(string $guildid, string $userid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['modify'], $guildid, $userid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function modifyCurrentNick(string $guildid, string $userid, string $nick) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['modifyCurrentNick'], $guildid, $userid);
        return $this->api->makeRequest('PATCH', $url, array('data' => array('nick' => $nick)));
    }
    
    function addGuildMemberRole(string $guildid, string $userid, string $roleid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['addRole'], $guildid, $userid, $roleid);
        return $this->api->makeRequest('PUT', $url, array());
    }
    
    function removeGuildMemberRole(string $guildid, string $userid, string $roleid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['members']['removeRole'], $guildid, $userid, $roleid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function getGuildBans(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['bans']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildBan(string $guildid, string $userid, int $daysDeleteMessages = 0) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['bans']['create'], $guildid, $userid);
        return $this->api->makeRequest('PUT', $url, array('data' => array('delete-message-days' => $daysDeleteMessages)));
    }
    
    function removeGuildBan(string $guildid, string $userid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['bans']['remove'], $guildid, $userid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function getGuildRoles(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['list'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function createGuildRole(string $guildid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['create'], $guildid);
        return $this->api->makeRequest('POST', $url, array('data' => $options));
    }
    
    function modifyGuildRolePositions(string $guildid, string $roleid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['modifyPositions'], $guildid, $roleid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function modifyGuildRole(string $guildid, string $roleid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['modify'], $guildid, $roleid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function deleteGuildRole(string $guildid, string $roleid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['roles']['delete'], $guildid, $roleid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function getGuildPruneCount(string $guildid, int $days) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['prune']['count'], $guildid);
        return $this->api->makeRequest('GET', $url, array('data' => array('days' => $days)));
    }
    
    function beginGuildPrune(string $guildid, int $days) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['prune']['begin'], $guildid);
        return $this->api->makeRequest('POST', $url, array('data' => array('days' => $days)));
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
    
    function modifyGuildIntegration(string $guildid, string $integrationid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['integrations']['modify'], $guildid, $integrationid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function deleteGuildInegration(string $guildid, string $integrationid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['integrations']['delete'], $guildid, $integrationid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function syncGuildIntegration(string $guildid, string $integrationid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['integrations']['sync'], $guildid, $integrationid);
        return $this->api->makeRequest('POST', $url, array());
    }
    
    function getGuildEmbed(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['embed']['get'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function modifyGuildEmbed(string $guildid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_GUILDS['embed']['modify'], $guildid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    // Invite
    
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
    
    // User
    
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
    
    // Voice
    
    function listVoiceRegions() {
        $url = Constants::format(Constants::ENDPOINTS_VOICES['regions']);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    // Webhook
    
    function createWebhook(string $channelid, string $nick, string $avatarBase64) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['create'], $channelid);
        return $this->api->makeRequest('POST', $url, array('data' => array('nick' => $nick, 'avatar' => $avatarBase64)));
    }
    
    function getChannelWebhooks(string $channelid) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['channels'], $channelid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getGuildsWebhooks(string $guildid) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['guilds'], $guildid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getWebhook(string $webhookid) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['get'], $webhookid);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function getWebhookToken(string $webhookid, string $token) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['getToken'], $webhookid, $token);
        return $this->api->makeRequest('GET', $url, array());
    }
    
    function modifyWebhook(string $webhookid, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['modify'], $webhookid);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function modifyWebhookToken(string $webhookid, string $token, array $options) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['modifyToken'], $webhookid, $token);
        return $this->api->makeRequest('PATCH', $url, array('data' => $options));
    }
    
    function deleteWebhook(string $webhookid) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['delete'], $webhookid);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function deleteWebhookToken(string $webhookid, string $token) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['deleteToken'], $webhookid, $token);
        return $this->api->makeRequest('DELETE', $url, array());
    }
    
    function executeWebhook(string $webhookid, string $token, array $options, array $files = array()) {
        $url = Constants::format(Constants::ENDPOINTS_WEBHOOKS['execute'], $webhookid, $token);
        return $this->api->makeRequest('POST', $url, array('data' => $options, 'files' => $files));
    }
}
