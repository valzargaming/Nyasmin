<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Voice;

/**
 * Represents a voice connection.
 * @todo Implementation of Voice
 */
class VoiceConnection extends \CharlotteDunois\Yasmin\Models\ClientBase {
    protected $channel;
    
    protected $sessionID;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Interfaces\VoiceChannelInterface $channel) {
        parent::__construct($client);
        $this->channel = $channel;
    }
    
    /**
     * @property-read string|null                                                    $sessionID   The voice session ID.
     * @property-read \CharlotteDunois\Yasmin\Interfaces\VoiceChannelInterface|null  $channel     The channel this voice state is for.
     *
     * @throws \Exception
     */
    function __get($name) {
        if(\property_exists($this, $name)) {
            return $this->$name;
        }
        
        return parent::__get($name);
    }
}
