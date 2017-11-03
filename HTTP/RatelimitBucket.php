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
     * @param \GuzzleHttp\Psr7\Response $response
     */
    function handleRatelimit(\GuzzleHttp\Psr7\Response $response) {
        $dateDiff = 0;
        if($response->hasHeader('Date')) {
            $dateDiff = \time() - ((new \DateTime($response->getHeader('Date')[0]))->getTimestamp());
        }
        
        if($response->hasHeader('X-RateLimit-Limit')) {
            if($response->hasHeader('X-RateLimit-Limit')) {
                $this->limit = (int) $response->getHeader('X-RateLimit-Limit')[0];
            }
            
            if($response->hasHeader('X-RateLimit-Remaining')) {
                $this->remaining = (int) $response->getHeader('X-RateLimit-Remaining')[0];
            }
            
            if($response->hasHeader('Retry-After')) {
                $this->resetTime = \time() + ((int) $response->getHeader('Retry-After')[0]);
            } else if($response->hasHeader('X-RateLimit-Reset')) {
                $this->resetTime = ((int) $response->getHeader('X-RateLimit-Reset')[0]) + $dateDiff;
            }
        } else {
            $this->remaining++; // there is no ratelimit...
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
        
        return ($this->remaining === 0);
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
