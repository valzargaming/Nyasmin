<?php
/**
 * Yasmin
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
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
    /**
     * The JSON encode/decode options.
     * @var int
     */
    protected $jsonOptions;
    
    /**
     * Constructor.
     */
    function __construct() {
        $this->jsonOptions = (\PHP_VERSION_ID >= 70300 ? \JSON_THROW_ON_ERROR : 0);
    }
    
    /**
     * Returns encoding name (for gateway query string).
     * @return string
     */
    function getName(): string {
        return 'json';
    }
    
    /**
     * Checks if the system supports it.
     * @return void
     * @throws \RuntimeException
     */
    static function supported(): void {
        // Nothing to check
    }
    
    /**
     * Decodes data.
     * @param string  $data
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    function decode(string $data) {
        $msg = \json_decode($data, true, 512, $this->jsonOptions);
        if($msg === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('The JSON decoder was unable to decode the data. Error: '.\json_last_error_msg());
        }
        
        return $msg;
    }
    
    /**
     * Encodes data.
     * @param mixed  $data
     * @return string
     * @throws \JsonException
     */
    function encode($data): string {
        return \json_encode($data, $this->jsonOptions);
    }
    
    /**
     * Prepares the data to be sent.
     * @param string  $data
     * @return \Ratchet\RFC6455\Messaging\Message
     */
    function prepareMessage(string $data): \Ratchet\RFC6455\Messaging\Message {
        $frame = new \Ratchet\RFC6455\Messaging\Frame($data, true, \Ratchet\RFC6455\Messaging\Frame::OP_TEXT);
        
        $msg = new \Ratchet\RFC6455\Messaging\Message();
        $msg->addFrame($frame);
        
        return $msg;
    }
}
