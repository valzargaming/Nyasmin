<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Interfaces;

/**
 * Something all guild channels implement.
 */
interface GuildChannelInterface {
    function createInvite(array $options = array());
    function clone(string $name = null, bool $withPermissions = true, bool $withTopic = true, string $reason = '');
    function edit(array $options, string $reason = '');
    function delete(string $reason = '');
    function fetchInvites();
    
    function permissionsFor($member);
    function overwritesFor($member);
    function overwritePermissions($memberOrRole, $allow, $deny = 0, string $reason = '');
    function lockPermissions(string $reason = '');
    
    function setName(string $name, string $reason = '');
    function setNSFW(bool $nsfw, string $reason = '');
    function setParent($parent, string $reason = '');
    function setPermissionOverwrites($permissionOverwrites, string $reason = '');
    function setPosition(int $position, string $reason = '');
    function setTopic(string $topic, string $reason = '');
}
