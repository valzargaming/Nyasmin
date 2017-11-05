<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP;

/**
 * Manages a route ratelimit.
 * @internal
 */
class RatelimitBucket {
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     */
    protected $api;
    
    /**
     * @var string
     */
    protected $endpoint;
    
    /**
     * @var int
     */
    protected $limit = 0;
    
    /**
     * @var int
     */
    protected $remaining = \INF;
    
    /**
     * @var int
     */
    protected $resetTime = 0;
    
    /**
     * @var array
     */
    protected $queue = array();
    
    /**
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager  $api
     * @param string                                   $endpoint
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api, string $endpoint) {
        $this->api = $api;
        $this->endpoint = $endpoint;
    }
    
    /**
     * Sets the ratelimits from the response
     * @param int|null  $limit
     * @param int|null  $remaining
     * @param int|null  $resetTime
     */
    function handleRatelimit(int $limit = null, int $remaining = null, int $resetTime = null) {
        if($limit === null && $remaining === null && $resetTime === null) {
            $this->remaining++; // there is no ratelimit...
            return;
        }
        
        if($limit !== null) {
            $this->limit = $limit;
        }
        
        if($remaining !== null) {
            $this->remaining = $remaining;
        }
        
        if($resetTime !== null) {
            $this->resetTime = $resetTime;
        }
    }
    
    /**
     * Returns the endpoint this bucket is for.
     * @return string
     */
    function getEndpoint() {
        return $this->endpoint;
    }
    
    /**
     * Returns the size of the queue
     * @var int
     */
    function size() {
        return \count($this->queue);
    }
    
    /**
     * Pushes a new request into the queue.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $request
     */
    function push(\CharlotteDunois\Yasmin\HTTP\APIRequest $request) {
        $this->queue[] = $request;
    }
    
    /**
     * Unshifts a new request into the queue. Modifies remaining ratelimit.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $request
     */
    function unshift(\CharlotteDunois\Yasmin\HTTP\APIRequest $request) {
        \array_unshift($this->queue, $request);
        $this->remaining++;
    }
    
    /**
     * Determines wether we've reached the ratelimit.
     * @return bool
     */
    function limited() {
        if($this->resetTime && \time() > $this->resetTime) {
            $this->resetTime = null;
            $this->remaining = ($this->limit ? $this->limit : \INF);
            
            return false;
        }
        
        return ($this->limit !== 0 && $this->remaining === 0);
    }
    
    /**
     * Returns the reset time.
     * @return int|null
     */
    function getResetTime() {
        return $this->resetTime;
    }
    
    /**
     * Returns the first queue item or false. Modifies remaining ratelimit.
     * @return \CharlotteDunois\Yasmin\HTTP\APIRequest|false
     */
    function shift() {
        if(\count($this->queue) === 0) {
            return false;
        }
        
        $item = \array_shift($this->queue);
        $this->remaining--;
        
        return $item;
    }
    
    /**
     * Unsets all queue items.
     */
    function clear() {
        $this->remaining = 0;
        while($item = \array_shift($this->queue)) {
            unset($item);
        }
    }
}
