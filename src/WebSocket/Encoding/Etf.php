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
    protected $etf;
    
    function __construct() {
        $this->etf = new \CharlotteDunois\Kimberly\Kimberly();
    }
    
    /**
     * Returns encoding name (for gateway query string).
     * @return string
     */
    function getName(): string {
        return 'etf';
    }
    
    /**
     * Checks if the system supports it.
     * @throws \RuntimeException
     * @return void
     */
    static function supported(): void {
        if(!\class_exists('\\CharlotteDunois\\Kimberly\\Kimberly')) {
            throw new \RuntimeException('Unable to use ETF as WS encoding due to missing dependencies');
        }
        
        if(\PHP_INT_SIZE < 8) {
            throw new \RuntimeException('ETF can not be used on with 32 bit PHP');
        }
    }
    
    /**
     * Decodes data.
     * @param string  $data
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \CharlotteDunois\Kimberly\Exception
     */
    function decode(string $data) {
        $msg = $this->etf->decode($data);
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
     * @throws \CharlotteDunois\Kimberly\Exception
     */
    function encode($data): string {
        $msg = $this->etf->encode($data);
        return $msg;
    }
    
    /**
     * Prepares the data to be sent.
     * @param string  $data
     * @return \Ratchet\RFC6455\Messaging\Message
     */
    function prepareMessage(string $data): \Ratchet\RFC6455\Messaging\Message {
        $frame = new \Ratchet\RFC6455\Messaging\Frame($data, true, \Ratchet\RFC6455\Messaging\Frame::OP_BINARY);
        
        $msg = new \Ratchet\RFC6455\Messaging\Message();
        $msg->addFrame($frame);
        
        return $msg;
    }
    
    /**
     * Converts all IDs from integer to strings.
     * @param array|object  $data
     * @return array|object
     */
    protected function convertIDs($data) {
        $arr = array();
        
        foreach($data as $key => $val) {
            if($val instanceof \CharlotteDunois\Kimberly\Atom) {
                $arr[$key] = (string) $val->atom;
            } elseif($val instanceof \CharlotteDunois\Kimberly\BaseObject) {
                $arr[$key] = $val->toArray();
            } elseif(\is_array($val) || \is_object($val)) {
                $arr[$key] = $this->convertIDs($val);
            } else {
                if(\is_int($val) && ($key === 'id' || \mb_substr($key, -3) === '_id')) {
                    $val = (string) $val;
                }
                
                $arr[$key] = $val;
            }
        }
        
        return (\is_object($data) ? ((object) $arr) : $arr);
    }
}
