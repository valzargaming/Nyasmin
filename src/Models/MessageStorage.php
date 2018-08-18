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
    protected $enabled;
    
    /**
     * @internal
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client, \CharlotteDunois\Yasmin\Interfaces\TextChannelInterface $channel, ?array $data = null) {
        parent::__construct($client, $data);
        $this->channel = $channel;
        $this->enabled = (bool) $this->client->getOption('messageCache', true);
        
        if($this->enabled) {
            $time = (int) $this->client->getOption('messageCacheLifetime', 0);
            $inv = (int) $this->client->getOption('messageSweepInterval', $time);
            
            if($inv > 0) {
                $this->timer = $this->client->addPeriodicTimer($inv, function () use ($time) {
                    $this->sweep($time);
                });
            }
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
     * Returns the item for a given key. If the key does not exist, null is returned.
     * @param mixed  $key
     * @return \CharlotteDunois\Yasmin\Models\Message|null
     */
    function get($key) {
        return parent::get($key);
    }
    
    /**
     * {@inheritdoc}
     * @return $this
     */
    function set($key, $value) {
        if(!$this->enabled) {
            return $this;
        }
        
        return parent::set($key, $value);
    }
    
    /**
     * Sweeps messages, deletes messages older than the parameter (timestamp - $time). Returns the amount of sweeped messages.
     * @param int  $time  0 = clear all
     * @return int
     */
    function sweep(int $time) {
        if($time <= 0) {
            $this->clear();
            return;
        }
        
        $amount = 0;
        foreach($this->data as $key => $msg) {
            if($msg->createdTimestamp > (\time() - $time)) {
                $this->delete($msg->id);
                unset($msg);
                
                $amount++;
            }
        }
        
        return $amount;
    }
}
