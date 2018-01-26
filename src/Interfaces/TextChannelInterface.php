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
 * Something all textchannels (all text-based channels) implement. See TextChannelTrait for full comments.
 */
interface TextChannelInterface {
    /**
     * Deletes multiple messages at once. Resolves with $this.
     * @param \CharlotteDunois\Yasmin\Utils\Collection|array|int  $messages
     * @param string                                              $reason
     * @param bool                                                $filterOldMessages
     * @return \React\Promise\Promise
     */
    function bulkDelete($messages, string $reason = '', bool $filterOldMessages = false);
    
    /**
     * Collects messages during a specific duration (and max. amount). Resolves with a Collection of Message instances, mapped by their IDs.
     * @param callable  $filter
     * @param array     $options
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function collectMessages(callable $filter, array $options = array());
    
    /**
     * Fetches a specific message using the ID. Resolves with an instance of Message.
     * @param string  $id
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function fetchMessage(string $id);
    
    /**
     * Fetches messages of this channel. Resolves with a Collection of Message instances, mapped by their ID.
     * @param array  $options
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function fetchMessages(array $options = array());
    
    /**
     * Sends a message to a channel. Resolves with an instance of Message, or a Collection of Message instances, mapped by their ID.
     * @param string  $content
     * @param array   $options
     * @return \React\Promise\Promise
     * @see \CharlotteDunois\Yasmin\Models\Message
     */
    function send(string $content, array $options = array());
    
    /**
     * Starts sending the typing indicator in this channel. Counts up a triggered typing counter.
     */
    function startTyping();
    
    /**
     * Stops sending the typing indicator in this channel. Counts down a triggered typing counter.
     * @param bool  $force
     */
    function stopTyping(bool $force = false);
    
    /**
     * Returns the amount of user typing in this channel.
     * @return int
     */
    function typingCount();
    
    /**
     * Determines whether the given user is typing in this channel or not.
     * @param \CharlotteDunois\Yasmin\Models\User  $user
     * @return bool
     */
    function isTyping(\CharlotteDunois\Yasmin\Models\User $user);
}
