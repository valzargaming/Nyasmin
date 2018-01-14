<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\WebSocket\Encoding;

/**
 * Handles WS encoding.
 * @internal
 */
class Etf implements \CharlotteDunois\Yasmin\Interfaces\WSEncodingInterface {
    protected $erlpack;
    
    function __construct() {
        $this->erlpack = new \CharlotteDunois\Erlpack\Erlpack(true, false);
    }
    
    /**
     * Returns encoding name (for gateway query string).
     * @return string
     */
    function getName() {
        return 'etf';
    }
    
    /**
     * Checks if the system supports it.
     * @throws \Exception
     */
    static function supported() {
        if(!\class_exists('\\CharlotteDunois\\Erlpack\\Erlpack')) {
            throw new \Exception('Unable to use ETF as WS encoding due to missing dependencies');
        }
        
        if(\PHP_INT_SIZE === 4) {
            throw new \Exception('ETF can not be used on with 32 bit PHP');
        }
    }
    
    /**
     * Decodes data.
     * @param string  $data
     * @return mixed
     * @throws \InvalidArgumentException|\CharlotteDunois\Erlpack\ErlpackException
     */
    function decode(string $data) {
        $msg = $this->erlpack->decode($data);
        if($msg === '' || $msg === null) {
            throw new \InvalidArgumentException('The ETF decoder was unable to decode the data');
        }
        
        $obj = $this->convertIDs($msg);
        return $obj;
    }
    
    /**
     * Encodes data.
     * @param mixed  $data
     * @return string
     * @throws \CharlotteDunois\Erlpack\ErlpackException
     */
    function encode($data) {
        $msg = $this->erlpack->encode($data);
        return $msg;
    }
    
    /**
     * Prepares the data to be sent.
     * @param string  $data
     * @return string|\Ratchet\RFC6455\Messaging\Message
     */
    function prepareMessage(string $data) {
        $frame = new \Ratchet\RFC6455\Messaging\Frame($data, true, \Ratchet\RFC6455\Messaging\Frame::OP_BINARY);
        
        $msg = new \Ratchet\RFC6455\Messaging\Message();
        $msg->addFrame($frame);
        
        return $msg;
    }
    
    /**
     * Converts all IDs from integer to strings.
     * @param array|object
     * @return array|object
     */
    protected function convertIDs($data) {
        $arr = array();
        
        foreach($data as $key => $val) {
            if(\is_array($val) || \is_object($val)) {
                $arr[$key] = $this->convertIDs($val);
            } else {
                if(\is_int($val) && ($key === 'id' || \mb_substr($key, -3) === '_id')) {
                    $val = (string) $val;
                }
                
                $arr[$key] = $val;
            }
        }
        
        return $arr;
    }
}
