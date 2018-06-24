<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin;

/**
 * Documents all Client events. ($client->on('name here', callable))
 *
 * The second parameter of *Update events is null, if cloning for that event is disabled.
 */
interface ClientEvents {
    /**
     * Emitted each time the client turns ready.
     */
    function ready();
    
    /**
     * Emitted when the client gets disconnected from the gateway.
     */
    function disconnect(int $code, string $reason);
    
    /**
     * Emitted when the client tries to reconnect.
     */
    function reconnect();
    
    /**
     * Emitted when we receive a message from the gateway.
     * @param mixed  $message
     */
    function raw($message);
    
    /**
     * Emitted when an uncached message gets deleted.
     */
    function messageDeleteRaw(\CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, string $messageID);
    
    /**
     * Emitted when multple uncached messages gets deleted.
     */
    function messageDeleteBulkRaw(\CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, array $messageIDs);
    
    /**
     * Emitted when an uncached message gets updated.
     * @see https://discordapp.com/developers/docs/topics/gateway#message-update
     * @see https://discordapp.com/developers/docs/resources/channel#message-object
     */
    function messageUpdateRaw(\CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, array $data);
    
    /**
     * Emitted when an error happens (inside the library or any listeners). You should always listen on this event.
     */
    function error(\Exception $error);
    
    /**
     * Debug messages.
     * @param string|\Exception  $message
     */
    function debug($message);
    
    /**
     * Ratelimit information.
     *
     * The array has the following format:
     * <pre>
     * array(
     *     'endpoint' => string,
     *     'global' => bool,
     *     'limit' => int|float, (float = \INF)
     *     'remaining => int,
     *     'resetTime' => int|null
     * )
     * </pre>
     */
    function ratelimit(array $data);
    
    /**
     * Emitted when a channel gets created.
     */
    function channelCreate(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel);
    
    /**
     * Emitted when a channel gets updated.
     */
    function channelUpdate(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $new, ?\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $old);
    
    /**
     * Emitted when a channel gets deleted.
     */
    function channelDelete(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel);
    
    /**
     * Emitted when a channel's pins gets updated. Due to the nature of the event, it's not possible to do much.
     */
    function channelPinsUpdate(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel, ?\DateTime $time);
    
    /**
     * Emitted when a guild gets joined.
     */
    function guildCreate(\CharlotteDunois\Yasmin\Models\Guild $guild);
    
    /**
     * Emitted when a guild gets updated.
     */
    function guildUpdate(\CharlotteDunois\Yasmin\Models\Guild $new, ?\CharlotteDunois\Yasmin\Models\Guild $old);
    
    /**
     * Emitted when a guild gets left.
     */
    function guildDelete(\CharlotteDunois\Yasmin\Models\Guild $guild);
    
    /**
     * Emitted when a guild becomes (un)available.
     */
    function guildUnavailable(\CharlotteDunois\Yasmin\Models\Guild $guild);
    
    /**
     * Emitted when someone gets banned.
     */
    function guildBanAdd(\CharlotteDunois\Yasmin\Models\Guild $guild, \CharlotteDunois\Yasmin\Models\User $user);
    
    /**
     * Emitted when someone gets unbanned.
     */
    function guildBanRemove(\CharlotteDunois\Yasmin\Models\Guild $guild, \CharlotteDunois\Yasmin\Models\User $user);
    
    /**
     * Emitted when an user joins a guild.
     */
    function guildMemberAdd(\CharlotteDunois\Yasmin\Models\GuildMember $member);
    
    /**
     * Emitted when a member gets updated.
     */
    function guildMemberUpdate(\CharlotteDunois\Yasmin\Models\GuildMember $new, ?\CharlotteDunois\Yasmin\Models\GuildMember $old);
    
    /**
     * Emitted when an user leaves a guild.
     */
    function guildMemberRemove(\CharlotteDunois\Yasmin\Models\GuildMember $member);
    
    /**
     * Emitted when the gateway sends requested members. The collection consists of GuildMember instances, mapped by their user ID.
     * @see \CharlotteDunois\Yasmin\Models\GuildMember
     */
    function guildMembersChunk(\CharlotteDunois\Yasmin\Models\Guild $guild, \CharlotteDunois\Yasmin\Utils\Collection $members);
    
    /**
     * Emitted when a role gets created.
     */
    function roleCreate(\CharlotteDunois\Yasmin\Models\Role $role);
    
    /**
     * Emitted when a role gets updated.
     */
    function roleUpdate(\CharlotteDunois\Yasmin\Models\Role $new, ?\CharlotteDunois\Yasmin\Models\Role $old);
    
    /**
     * Emitted when a role gets deleted.
     */
    function roleDelete(\CharlotteDunois\Yasmin\Models\Role $role);
    
    /**
     * Emitted when a message gets received.
     */
    function message(\CharlotteDunois\Yasmin\Models\Message $message);
    
    /**
     * Emitted when a (cached) message gets updated.
     */
    function messageUpdate(\CharlotteDunois\Yasmin\Models\Message $new, ?\CharlotteDunois\Yasmin\Models\Message $old);
    
    /**
     * Emitted when a (cached) message gets deleted.
     */
    function messageDelete(\CharlotteDunois\Yasmin\Models\Message $message);
    
    /**
     * Emitted when multiple (cached) message gets deleted. The collection consists of Message instances, mapped by their ID.
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function messageDeleteBulk(\CharlotteDunois\Yasmin\Utils\Collection $messages);
    
    /**
     * Emitted when someone reacts to a (cached) message.
     */
    function messageReactionAdd(\CharlotteDunois\Yasmin\Models\MessageReaction $reaction, \CharlotteDunois\Yasmin\Models\User $user);
    
    /**
     * Emitted when a reaction from a (cached) message gets removed.
     */
    function messageReactionRemove(\CharlotteDunois\Yasmin\Models\MessageReaction $reaction, \CharlotteDunois\Yasmin\Models\User $user);
    
    /**
     * Emitted when all reactions from a (cached) message gets removed.
     */
    function messageReactionRemoveAll(\CharlotteDunois\Yasmin\Models\Message $message);
    
    /**
     * Emitted when a presence updates.
     */
    function presenceUpdate(\CharlotteDunois\Yasmin\Models\Presence $new, ?\CharlotteDunois\Yasmin\Models\Presence $old);
    
    /**
     * Emitted when someone starts typing in the channel.
     */
    function typingStart(\CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, \CharlotteDunois\Yasmin\Models\User $user);
    
    /**
     * Emitted when someone stops typing in the channel.
     */
    function typingStop(\CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, \CharlotteDunois\Yasmin\Models\User $user);
    
    /**
     * Emitted when someone updates their user account (username/avatar/etc.).
     */
    function userUpdate(\CharlotteDunois\Yasmin\Models\User $new, ?\CharlotteDunois\Yasmin\Models\User $old);
    
    /**
     * Emitted when a member's voice state changes (leaves/joins/etc.).
     */
    function voiceStateUpdate(\CharlotteDunois\Yasmin\Models\GuildMember $new, ?\CharlotteDunois\Yasmin\Models\GuildMember $old);
}
