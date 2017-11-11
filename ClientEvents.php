<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin;

/**
 * Documents all Client events. ($client->on('name here', $callable))
 *
 * @method  ready()                                                                                                                                  Emitted when the client is ready.
 * @method  disconnect()                                                                                                                             Emitted when the client gets disconnected from the gateway.
 * @method  reconnect()                                                                                                                              Emitted when the client tries to reconnect.
 * @method  channelCreate(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel)                                                              Emitted when a channel gets created.
 * @method  channelUpdate(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $new, \CharlotteDunois\Yasmin\Interfaces\ChannelInterface|null $old)   Emitted when a channel gets updated.
 * @method  channelDelete(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel)                                                              Emitted when a channel gets deleted.
 * @method  channelPinsUpdate(\CharlotteDunois\Yasmin\Interfaces\ChannelInterface $channel, \DateTime|null $time)                                    Emitted when a channel's pins gets updated. Due to the nature of the event, it's not possible to do much.
 * @method  guildCreate(\CharlotteDunois\Yasmin\Models\Guild $guild)                                                                                 Emitted when a guild gets joined.
 * @method  guildUpdate(\CharlotteDunois\Yasmin\Models\Guild $new, \CharlotteDunois\Yasmin\Models\Guild|null $old)                                   Emitted when a guild gets updated.
 * @method  guildDelete(\CharlotteDunois\Yasmin\Models\Guild $guild)                                                                                 Emitted when a guild gets left.
 * @method  guildUnavailable(\CharlotteDunois\Yasmin\Models\Guild $guild)                                                                            Emitted when a guild becomes (in)available.
 * @method  guildBanAdd(\CharlotteDunois\Yasmin\Models\Guild $guild, \CharlotteDunois\Yasmin\Models\User $user)                                      Emitted when someone gets banned.
 * @method  guildBanRemove(\CharlotteDunois\Yasmin\Models\Guild $guild, \CharlotteDunois\Yasmin\Models\User $user)                                   Emitted when someone gets unbanned.
 * @method  guildMemberAdd(\CharlotteDunois\Yasmin\Models\GuildMember $member)                                                                       Emitted when an user joins a guild.
 * @method  guildMemberUpdate(\CharlotteDunois\Yasmin\Models\GuildMember $new, \CharlotteDunois\Yasmin\Models\GuildMember $old)                      Emitted when a member gets updated.
 * @method  guildMemberRemove(\CharlotteDunois\Yasmin\Models\GuildMember $member)                                                                    Emitted when an user leaves a guild.
 * @method  guildMembersChunk(\CharlotteDunois\Yasmin\Models\Guild $guild, \CharlotteDunois\Yasmin\Utils\Collection $members)                        Emitted when the gateway sends requested members. The collection consists of GuildMember objects, mapped by their user ID. {@see \CharlotteDunois\Yasmin\Models\GuildMember}
 * @method  roleCreate(\CharlotteDunois\Yasmin\Models\Role $role)                                                                                    Emitted when a role gets created.
 * @method  roleUpdate(\CharlotteDunois\Yasmin\Models\Role $new, \CharlotteDunois\Yasmin\Models\Role $old)                                           Emitted when a role gets updated.
 * @method  roleDelete(\CharlotteDunois\Yasmin\Models\Role $role)                                                                                    Emitted when a role gets deleted.
 * @method  message(\CharlotteDunois\Yasmin\Models\Message $message)                                                                                 Emitted when a message gets received.
 * @method  messageUpdate(\CharlotteDunois\Yasmin\Models\Message $new, \CharlotteDunois\Yasmin\Models\Message $old)                                  Emitted when a (cached) message gets updated.
 * @method  messageDelete(\CharlotteDunois\Yasmin\Models\Message $message)                                                                           Emitted when a (cached) message gets deleted.
 * @method  messageDeleteBulk(\CharlotteDunois\Yasmin\Utils\Collection $messages)                                                                    Emitted when multiple (cached) message gets deleted. The collection consists of Message objects, mapped by their ID. {@see \CharlotteDunois\Yasmin\Models\Message}
 * @method  messageReactionAdd(\CharlotteDunois\Yasmin\Models\MessageReaction $reaction)                                                             Emitted when someone reacts to a (cached) message.
 * @method  messageReactionRemove(\CharlotteDunois\Yasmin\Models\MessageReaction $reaction)                                                          Emitted when a reaction from a (cached) message gets removed.
 * @method  messageReactionRemoveAll(\CharlotteDunois\Yasmin\Models\Message $message)                                                                Emitted when all reactions from a (cached) message gets removed.
 * @method  presenceUpdate(\CharlotteDunois\Yasmin\Models\Presence $presence)                                                                        Emitted when a presence updates.
 * @method  typingStart(\CharlotteDunois\Yasmin\Models\TextBasedChannel $channel, \CharlotteDunois\Yasmin\Models\User $user)                         Emitted when someone starts typing in the channel.
 * @method  userUpdate(\CharlotteDunois\Yasmin\Models\User $new, \CharlotteDunois\Yasmin\Models\User $old)                                           Emitted when someone updates their user account (username/avatar/etc.).
 * @method  voiceStateUpdate(\CharlotteDunois\Yasmin\Models\GuildMember $new, \CharlotteDunois\Yasmin\Models\GuildMember $old)                       Emitted when a member's voice state changes (leaves/joins/etc.).
 *
 * @method  raw(array $message)                                                                                                                      Emitted when we receive a message from the gateway.
 * @method  messageDeleteRaw(\CharlotteDunois\Yasmin\Models\TextBasedChannel $channel, string $messageID)                                            Emitted when an uncached message gets deleted.
 * @method  messageDeleteBulkRaw(\CharlotteDunois\Yasmin\Models\TextBasedChannel $channel, array $messageIDs)                                        Emitted when multple uncached messages gets deleted.
 * @method  error(\Exception $error)                                                                                                                 Emitted when an error happens.
 * @method  debug(string $message)                                                                                                                   Debug messages.
 */
interface ClientEvents {
    
}
