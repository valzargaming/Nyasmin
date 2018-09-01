<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

/**
 * Represents a shard.
 *
 * @property int  $id  The shard ID.
 */
class Shard extends ClientBase {
    /**
     * @var int
     */
    protected $id;
    
    /**
     * @var \CharlotteDunois\Yasmin\WebSocket\WSConnection
     */
    protected $ws;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, int $shardID, \CharlotteDunois\Yasmin\WebSocket\WSConnection $connection) {
        parent::__construct($client);
        
        $this->id = $shardID;
        $this->ws = $connection;
    }
    
    /**
     * {@inheritdoc}
     * @return mixed
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
}
