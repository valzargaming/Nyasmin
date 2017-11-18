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
 * @internal
 */
class ZlibStream
    implements \CharlotteDunois\Yasmin\Interfaces\WSCompressionInterface {
    
    protected $context;
    
    function getName() {
        return 'zlib-stream';
    }
    
    /**
     * Initializes the context.
     */
    function init() {
        $this->context = \inflate_init(ZLIB_ENCODING_DEFLATE);
        if(!$this->context) {
            throw new \Exception('Unable to initialize Zlib Inflate');
        }
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
     * Returns a boolean for the OP code 2 IDENTIFY packet 'compress' parameter. The parameter is for payload compression.
     * @return bool
     */
    function payloadCompression() {
        return false;
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
        if($uncompressed === false) {
            throw new \InvalidArgumentException('The inflate context was unable to decompress the data');
        }
        
        return $uncompressed;
    }
}
