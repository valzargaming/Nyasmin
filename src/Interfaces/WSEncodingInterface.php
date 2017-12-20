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
 * Interface for WS compressions.
 * @internal
 */
interface WSEncodingInterface {
    /**
     * Returns encoding name (for gateway query string).
     */
    function getName();
    
    /**
     * Initializes the context.
     */
    function init();
    
    /**
     * Destroys the context.
     */
    function destroy();
    
    /**
     * Checks if the system supports it.
     * @throws \Exception
     */
    static function supported();
    
    /**
     * Decodes data.
     * @param string  $data
     * @return mixed
     * @throws \BadMethodCallException|\InvalidArgumentException
     */
    function decode(string $data);
    
    /**
     * Encodes data.
     * @param mixed  $data
     * @return string
     * @throws \BadMethodCallException|\InvalidArgumentException
     */
    function encode($data);
    
    /**
     * Prepares the data to be sent.
     * @return string|\Ratchet\RFC6455\Messaging\Message
     */
    function prepareMessage(string $data);
}
