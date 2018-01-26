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
 * Message Storage to store and handle messages, utilizes Collection.
 */
class MessageStorage extends Storage {
    protected $channel;
    protected $timer;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, ?array $data = null) {
        parent::__construct($client, $data);
        $this->channel = $channel;
        
        $time = (int) $this->client->getOption('messageCacheLifetime', 0);
        if($time > 0) {
            $this->timer = $this->client->addPeriodicTimer((int) $this->client->getOption('messageSweepInterval', $time), function () use ($time) {
                $this->sweep($time);
            });
        }
    }
    
    /**
     * @internal
     */
    function __destruct() {
        if($this->timer) {
            $this->client->cancelTimer($this->timer);
        }
    }
    
    /**
     * Sweeps messages, deletes messages older than the parameter (timestamp - $time).
     * @param int  $time  0 = clear all
     */
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
