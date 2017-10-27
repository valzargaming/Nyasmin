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
 * Something all Models extend. Do not use this.
 * @access private
 */
class Base implements \JsonSerializable, \Serializable { //TODO: Nya
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
        $vars = \get_object_vars($this);
        
        foreach($vars as $key => $val) {
            if($val instanceof \Closure) {
                unset($vars[$key]);
            }
        }
        
        return \serialize($vars);
    }
    
    /**
     * @access private
     */
    function unserialize($data) {
        $this->__construct(\unserialize($data));
    }
    
    /**
     * @access private
     */
    function _patch(array $data) {
        foreach($data as $key => $val) {
            if(\strpos($key, '_') !== false) {
                $key = \lcfirst(\str_replace('_', '', \ucwords($key, '_')));
            }
            
            if(\property_exists($this, $key)) {
                if($this->$key instanceof \CharlotteDunois\Yasmin\Models\Collection) {
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
    
    /**
     * @access private
     */
    function _shouldUpdate(array $data) {
        $oldData = \json_decode(\json_encode($this), true);
        
        foreach($data as $key => $val) {
            if(\strpos($key, '_') !== false) {
                $key = \lcfirst(\str_replace('_', '', \ucwords($key, '_')));
            }
            
            if(\array_key_exists($key, $oldData) && $oldData[$key] !== $val) {
                return true;
            }
        }
        
        return false;
    }
}
