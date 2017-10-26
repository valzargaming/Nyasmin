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
 * Handles WS compression.
 * @access private
 */
class ZlibStream
    implements \CharlotteDunois\Yasmin\WebSocket\Compression\CompressionInterface {
    
    protected $context;
    
    /**
     * Initializes the context.
     */
    function init() {
        $this->context = \inflate_init(ZLIB_ENCODING_DEFLATE);
    }
    
    /**
     * Destroys the context.
     */
    function destroy() {
        $this->context = null;
    }
    
    /**
     * Checks if the system supports it.
     * @throws \Exception
     */
    static function supported() {
        if(!\function_exists('\inflate_init')) {
            throw new \RuntimeException('Zlib is not supported by this PHP installation');
        }
    }
    
    /**
     * Decompresses data.
     * @param string  $data
     * @return string
     * @throws \BadMethodCallException|\InvalidArgumentException
     */
    function decompress(string $data) {
        if(!$this->context) {
            throw new \BadMethodCallException('No inflate context initialized');
        }
        
        $uncompressed = \inflate_add($this->context, $data);
        if(!$uncompressed) {
            throw new \InvalidArgumentException('The inflate context was unable to decompress the data');
        }
        
        return $uncompressed;
    }
}
