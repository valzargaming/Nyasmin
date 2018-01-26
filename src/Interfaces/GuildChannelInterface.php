<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Interfaces;

/**
 * Something all guild channels implement. See GuildChannelTrait for full comments.
 */
interface GuildChannelInterface {
    /**
     * Creates an invite. Resolves with an instance of Invite.
     * @param array $options
     * @return \React\Promise\Promise
     */
    function createInvite(array $options = array());
    
    /**
     * Clones a guild channel. Resolves with an instance of GuildChannelInterface.
     * @param string  $name
     * @param bool    $withPermissions
     * @param bool    $withTopic
     * @param string  $reason
     * @return \React\Promise\Promise
     */
    function clone(?string $name = null, bool $withPermissions = true, bool $withTopic = true, string $reason = '');
     
    /**
     * Edits the channel. Resolves with $this.
     * @param array   $options
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function edit(array $options, string $reason = '');
    
    /**
     * Deletes the channel.
     * @param string  $reason
     * @return \React\Promise\Promise
     */
    function delete(string $reason = '');
    
    /**
     * Fetches all invites of this channel. Resolves with a Collection of Invite instances, mapped by their code.
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Invite
     */
    function fetchInvites();
    
    /**
     * Returns the permissions for the given member.
     * @param \CharlotteDunois\Yasmin\Models\GuildMember|string  $member
     * @return \CharlotteDunois\Yasmin\Models\Permissions
     * @throws \InvalidArgumentException
     */
    function permissionsFor($member);
    
    /**
     * Returns the permissions overwrites for the given member.
     * @param \CharlotteDunois\Yasmin\Models\GuildMember|string  $member
     * @return array
     * @throws \InvalidArgumentException
     */
    function overwritesFor($member);
    
    /**
     * Overwrites the permissions for a member or role in this channel. Resolves with an instance of PermissionOverwrite.
     * @param \CharlotteDunois\Yasmin\Models\GuildMember|\CharlotteDunois\Yasmin\Models\Role|string  $memberOrRole
     * @param \CharlotteDunois\Yasmin\Models\Permissions|int                                         $allow
     * @param \CharlotteDunois\Yasmin\Models\Permissions|int                                         $deny
     * @param string                                                                                 $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function overwritePermissions($memberOrRole, $allow, $deny = 0, string $reason = '');
    
    /**
     * Locks in the permission overwrites from the parent channel. Resolves with $this.
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \BadMethodCallException
     */
    function lockPermissions(string $reason = '');
    
    /**
     * Sets the name of the channel. Resolves with $this.
     * @param string  $name
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function setName(string $name, string $reason = '');
    
    /**
     * Sets the nsfw flag of the channel. Resolves with $this.
     * @param bool    $nsfw
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function setNSFW(bool $nsfw, string $reason = '');
    
    /**
     * Sets the parent of the channel. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Models\CategoryChannel|string  $parent
     * @param string                                                 $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function setParent($parent, string $reason = '');
    
    /**
     * Sets the permission overwrites of the channel. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Utils\Collection|array  $permissionOverwrites
     * @param string                                          $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function setPermissionOverwrites($permissionOverwrites, string $reason = '');
    
    /**
     * Sets the position of the channel. Resolves with $this.
     * @param int     $position
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function setPosition(int $position, string $reason = '');
    
    /**
     * Sets the topic of the channel. Resolves with $this.
     * @param string  $topic
     * @param string  $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function setTopic(string $topic, string $reason = '');
}
