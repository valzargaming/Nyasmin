<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Encoding;

/**
 * Handles WS encoding.
 * @internal
 */
class Json implements \CharlotteDunois\Yasmin\Interfaces\WSEncodingInterface {
    
    function getName() {
        return 'json';
    }
    
    /**
     * Initializes the encoder.
     */
    function init() {
        
    }
    
    /**
     * Destroys the encoder.
     */
    function destroy() {
        
    }
    
    /**
     * Checks if the system supports it.
     * @throws \Exception
     */
    static function supported() {
        // Nothing to check
    }
    
    /**
     * Decodes data.
     * @param string  $data
     * @return mixed
     * @throws \BadMethodCallException|\InvalidArgumentException
     */
    function decode(string $data) {
        $msg = \json_decode($data, true);
        if($msg === null || \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('The JSON decoder was unable to decode the data. Error: '.\json_last_error_msg());
        }
        
        return $msg;
    }
    
    /**
     * Encodes data.
     * @param mixed  $data
     * @return string
     * @throws \BadMethodCallException|\InvalidArgumentException
     */
    function encode($data) {
        return \json_encode($data);
    }
    
    /**
     * Prepares the data to be sent.
     * @return string|\Ratchet\RFC6455\Messaging\Message
     */
    function prepareMessage(string $data) {
        return $data;
    }
}
