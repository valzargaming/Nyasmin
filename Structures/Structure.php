<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

/**
 * Something all structures extend. Do not use this.
 * @access private
 */
class Structure implements \JsonSerializable, \Serializable { //TODO: Nya
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
     * @access private
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
        return get_object_vars($this);
    }
    
    /**
     * @access private
     */
    function serialize() {
        return serialize(get_object_vars($this));
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
                $this->__construct(unserialize($data));
            break;
            case 2:
                $this->__construct(\CharlotteDunois\Yasmin\Structures\Structure::$serializeClient, unserialize($data));
            break;
        }
    }
    
    /**
     * @access private
     */
    function _patch(array $data) {
        foreach($data as $key => $val) {
            $key = \lcfirst(\str_replace(' ', '', \ucwords(\str_replace('_', ' ', $key))));
            
            if(\property_exists($this, $key)) {
                if($this->$key instanceof \CharlotteDunois\Yasmin\Structures\Collection) {
                    if(!\is_array($val)) {
                        $val = array($val);
                    }
                    
                    foreach($val as $element) {
                        $instance = $this->$key->get($element['id']);
                        $instance->_patch($element);
                    }
                } else {
                    if(\is_object($this->$key)) {
                        if(\is_array($val)) {
                            $this->$key = clone $this->$key;
                            $this->$key->_patch($val);
                        } else {
                            //TODO: Implementation
                            $this->client->emit('debug', 'Manual update of '.$key.' in '.\get_class($this).' required');
                        }
                    } else {
                        if($this->$key !== $val) {
                            $this->$key = $val;
                        }
                    }
                }
            }
        }
    }
}
