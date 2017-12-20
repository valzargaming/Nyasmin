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
 * Interface for WS compressions. This is used internally.
 */
interface WSCompressionInterface {
    /**
     * Returns compression name (for gateway query string).
     * @return string
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
     * Returns a boolean for the OP code 2 IDENTIFY packet 'compress' parameter. The parameter is for payload compression.
     * @return bool
     */
    function payloadCompression();
    
    /**
     * Decompresses data.
     * @param string  $data
     * @return string
     * @throws \BadMethodCallException|\InvalidArgumentException
     */
    function decompress(string $data);
}
