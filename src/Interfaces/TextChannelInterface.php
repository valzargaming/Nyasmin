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
 * Something all textchannels (all text-based channels) implement.
 */
interface TextChannelInterface {
    function bulkDelete($messages, string $reason = '', bool $filterOldMessages = false);
    function collectMessages(callable $filter, array $options = array());
    
    function fetchMessage(string $id);
    function fetchMessages(array $options = array());
    function send(string $content, array $options = array());
    
    function startTyping();
    function stopTyping(bool $force = false);
    function typingCount();
    function isTyping(\CharlotteDunois\Yasmin\Models\User $user);
}
