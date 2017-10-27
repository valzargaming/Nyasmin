<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Models;

class GroupDMChannel extends DMChannel { //TODO: Implementation
    protected $applicationID;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $channel) {
        parent::__construct($client, $channel);
        
        $this->applicationID = $channel['application_id'] ?? null;
    }
    
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        switch($name) {
            
        }
        
        return parent::__get($name);
    }
}
