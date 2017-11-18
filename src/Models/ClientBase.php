<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Something all Models, with the need for a client, extend. Do not use this.
 * @property \CharlotteDunois\Yasmin\Client  $client  The client which initiated the instance.
 */
class ClientBase extends Base {
    /**
     * @internal
     * @var \CharlotteDunois\Yasmin\Client
     */
    protected $client;
    
    /**
     * @internal
     * @var \CharlotteDunois\Yasmin\Client|null
     */
    static public $serializeClient;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    /**
     * @inheritDoc
     *
     * @internal
     */
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
        }
        
        return parent::__get($name);
    }
    
    /**
     * @internal
     */
    function __debugInfo() {
        $vars = \get_object_vars($this);
        unset($vars['client']);
        return $vars;
    }
    
    /**
     * @internal
     */
    function jsonSerialize() {
        $vars = parent::jsonSerialize();
        unset($vars['client']);
        return $vars;
    }
    
    /**
     * @internal
     */
    function unserialize($data) {
        $exp = \ReflectionMethod::export($this, '__construct', true);
        preg_match('/Parameters \[(\d+)\]/', $exp, $count);
        $count = $count[1];
        
        switch($count) {
            default:
                throw new \Exception('Unable to unserialize a class with more than 2 arguments');
            break;
            case 1:
                $this->__construct(\unserialize($data));
            break;
            case 2:
                $this->__construct(self::$serializeClient, unserialize($data));
            break;
        }
    }
}
