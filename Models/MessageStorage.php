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
 * @internal
 * @todo Docs
 */
class MessageStorage extends Storage {
    function __construct(\CharlotteDunois\Yasmin\Client $client, array $data = null) {
        parent::__construct($client, $data);
        
        $time = (int) $this->client->getOption('messageCacheLifetime', 0);
        if($time > 0) {
            $this->client->addPeriodicTimer($time, function () use ($time) {
                $this->sweep($time);
            });
        }
    }
    
    function sweep(int $time) {
        if($time === 0) {
            $this->clear();
            return;
        }
        
        foreach($this->data as $key => $msg) {
            if($msg->createdTimestamp > (\time() - $time)) {
                $this->delete($msg->id);
            }
        }
    }
}
