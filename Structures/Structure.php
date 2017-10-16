<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class Structure implements \JsonSerializable, \Serializable { //TODO
    protected $client;
    static public $serializeClient;
    
    function __construct($client) {
        $this->client = $client;
    }
    
    function client() {
        return $this->client;
    }
    
    function jsonSerialize() {
        return get_object_vars($this);
    }
    
    function serialize() {
        return serialize(get_object_vars($this));
    }
    
    function unserialize($data) {
        $this->__construct(\CharlotteDunois\Yasmin\Structures\Structure::$serializeClient, unserialize($data));
    }
    
    function _patch(array $data) {
        foreach($data as $key => $val) {
            $this->$key = $val;
        }
    }
}
