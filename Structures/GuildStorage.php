<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Structures;

class GuildStorage extends Collection { //TODO: Docs
    protected $client;
    
    function __construct($client, array $data = null) {
        parent::__construct($data);
    }
    
    function client() {
        return $this->client;
    }
    
    function resolve($guild) {
        if($guild instanceof \CharlotteDunois\Yasmin\Structures\Guild) {
            return $guild;
        }
        
        if(is_string($guild) && $this->has($guild)) {
            return $this->get($guild);
        }
        
        throw new \Exception('Unable to resolve unknown guild');
    }
}
