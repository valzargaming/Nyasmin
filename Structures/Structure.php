<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Structures;

/**
 * Something all structures, with the need for a client, extend. Do not use this.
 */
class Structure extends Part { //TODO: Nya
    /**
     * @access private
     */
    protected $client;
    
    /**
     * @access private
     */
    static public $serializeClient;
    
    /**
     * @access private
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
    }
    
    /**
     * @property-read \CharlotteDunois\Yasmin\Client  $client
     */
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
        }
        
        return null;
    }
    
    /**
     * @access private
     */
    function __debugInfo() {
        $vars = \get_object_vars($this);
        unset($vars['client']);
        return $vars;
    }
    
    /**
     * @access private
     */
    function jsonSerialize() {
        $vars = parent::jsonSerialize();
        unset($vars['client']);
        return $vars;
    }
    
    /**
     * @access private
     */
    function unserialize($data) {
        $exp = \ReflectionMethod::export($this, '__construct', true);
        preg_match('/Parameters \[(\d+)\]/', $exp, $count);
        $count = $count[1];
        
        switch($count) {
            default:
                throw new \Exception('Can not unserialize a class with more than 2 arguments');
            break;
            case 1:
                $this->__construct(\unserialize($data));
            break;
            case 2:
                $this->__construct(\CharlotteDunois\Yasmin\Structures\Structure::$serializeClient, unserialize($data));
            break;
        }
    }
}
