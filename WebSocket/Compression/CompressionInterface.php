<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Compression;

/**
 * Interface for WS compressions.
 * @access private
 */
interface CompressionInterface {
    /**
     * Returns compression name (for gateway query string).
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
     * Decompresses data.
     * @param string  $data
     * @return string
     * @throws \BadMethodCallException|\InvalidArgumentException
     */
    function decompress(string $data);
}
