<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Interfaces;

interface TextChannelInterface { //TODO: Implementation //TODO: Docs
    function acknowledge();
    function awaitMessages(callable $filter, array $options = array());
    function bulkDelete($messages);
    function search(array $options = array());
    
    function send(string $message, array $options = array());
    
    function startTyping();
    function stopTyping(bool $force = false);
}
