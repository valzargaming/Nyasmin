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
    /**
     * Creates an invite. Resolves with an instance of Invite.
     *
     * Options are as following (all are optional).
     *
     * <pre>
     * array(
     *    'maxAge' => int,
     *    'maxUses' => int, (0 = unlimited)
     *    'temporary' => bool,
     *    'unique' => bool
     * )
     * </pre>
     *
     * @param array $options
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Invite
     */
    function createInvite(array $options = array());
    
    /**
     * Clones a guild channel. Resolves with an instance of GuildChannelInterface.
     * @param string  $name             Optional name for the new channel, otherwise it has the name of this channel.
     * @param bool    $withPermissions  Whether to clone the channel with this channel's permission overwrites
     * @param bool    $withTopic        Whether to clone the channel with this channel's topic.
     * @param string  $reason
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Interfaces\GuildChannelInterface
     */
    function clone(string $name = null, bool $withPermissions = true, bool $withTopic = true, string $reason = '');
     
    /**
     * Edits the channel. Resolves with $this.
     *
     * Options are as following (at least one is required).
     *
     * <pre>
     * array(
     *    'name' => string,
     *    'position' => int,
     *    'topic' => string, (text channels only)
     *    'nsfw' => bool, (text channels only)
     *    'bitrate' => int, (voice channels only)
     *    'userLimit' => int, (voice channels only)
     *    'parent' => \CharlotteDunois\Yasmin\Models\CategoryChannel|string, (string = channel ID)
     *    'permissionOverwrites' => \CharlotteDunois\Yasmin\Utils\Collection|array (an array or Collection of PermissionOverwrite instances or permission overwrite arrays)
     * )
     * </pre>
     *
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
     * @param \CharlotteDunois\Yasmin\Models\GuildMember|\CharlotteDunois\Yasmin\Models\Role|string  $memberOrRole  The member or role.
     * @param \CharlotteDunois\Yasmin\Models\Permissions|int                                         $allow         Which permissions should be allowed?
     * @param \CharlotteDunois\Yasmin\Models\Permissions|int                                         $deny          Which permissions should be denied?
     * @param string                                                                                 $reason        The reason for this.
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     * @see \CharlotteDunois\Yasmin\Models\PermissionOverwrite
     */
    function overwritePermissions($memberOrRole, $allow, $deny = 0, string $reason = '');
    
    /**
     * Locks in the permission overwrites from the parent channel. Resolves with $this.
     * @param string  $reason  The reason for this.
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
     * @param \CharlotteDunois\Yasmin\Models\CategoryChannel|string  $parent  An instance of CategoryChannel or the channel ID.
     * @param string                                                 $reason
     * @return \React\Promise\Promise
     * @throws \InvalidArgumentException
     */
    function setParent($parent, string $reason = '');
    
    /**
     * Sets the permission overwrites of the channel. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Utils\Collection|array  $permissionOverwrites  An array or Collection of PermissionOverwrite instances or permission overwrite arrays.
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
