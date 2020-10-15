<?php
/**
 * Yasmin
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP;

/**
 * Manages a route's ratelimit in memory.
 * @internal
 */
class RatelimitBucket extends AbstractRatelimitBucket implements \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface {
    /**
     * The API manager.
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     */
    protected $api;
    
    /**
     * The endpoint.
     * @var string
     */
    protected $endpoint;
    
    /**
     * The requests limit.
     * @var int
     */
    protected $limit = 0;
    
    /**
     * How many requests can be made.
     * @var int
     */
    protected $remaining = \INF;
    
    /**
     * When the ratelimit gets reset.
     * @var float
     */
    protected $resetTime = 0.0;
	
	/**
     * The bucket header.
     * @var string
     */
	 protected $bucketHeader;
    
    /**
     * The request queue.
     * @var \CharlotteDunois\Yasmin\HTTP\APIRequest[]
     */
    protected $queue = array();
    
    /**
     * Whether the bucket is busy.
     * @var bool
     */
    protected $busy = false;
    
    /**
     * DO NOT initialize this class yourself.
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager  $api
     * @param string                                   $endpoint
     */
	 /*
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api, string $endpoint, ?string $bucketHeader = null) {
        $this->api = $api;
        $this->endpoint = $endpoint;
		//$this->bucketHeader = $bucketHeader;
    }
	*/
    
    /**
     * Destroys the bucket.
     */
    function __destruct() {
        $this->clear();
    }
    
    /**
     * Whether we are busy.
     * @return bool
     */
    function isBusy(): bool {
        return $this->busy;
    }
    
    /**
     * Sets the busy flag (marking as running).
     * @param bool  $busy
     * @return void
     */
    function setBusy(bool $busy): void {
        $this->busy = $busy;
    }
    
    /**
     * Sets the ratelimits from the response.
     * @param int|null    $limit
     * @param int|null    $remaining
     * @param float|null  $resetTime  Reset time in seconds with milliseconds.
     * @return \React\Promise\ExtendedPromiseInterface|void
     */
    function handleRatelimit(?int $limit, ?int $remaining, ?float $resetTime) {
        if ($limit === null && $remaining === null && $resetTime === null) {
            $this->remaining++; // there is no ratelimit...
            return;
        }
        
        $this->limit = $limit ?? $this->limit;
        $this->remaining = $remaining ?? $this->remaining;
        $this->resetTime = $resetTime ?? $this->resetTime;
		$this->bucketHeader = $bucketHeader ?? $this->bucketHeader;
        
        if ($this->remaining === 0 && $this->resetTime > \microtime(true)) {
            $this->api->client->emit('debug', 'Endpoint "'.$this->endpoint.'" and Bucket "'.$this->bucketHeader.'" ratelimit encountered, continuing in '.($this->resetTime - \microtime(true)).' seconds');
        }
    }
	function handleRatelimitNew(?int $limit, ?int $remaining, ?float $resetTime) {
        if ($limit === null && $remaining === null && $resetTime === null) {
            return; // there is no ratelimit...
        }
        
		//There's a ratelimit, so we need to set some time for when to call this again
		$bucketHeader = $this->client->bucketHeader;
		//
		//
		
		if ($this->client->xBuckets[$bucketHeader]['remaining'] === 0 && $this->resetTime > \microtime(true)){
			$this->api->client->emit('debug', 'Endpoint "'.$this->endpoint.'" and Bucket "'.$bucketHeader.'" ratelimit encountered, continuing in '.($this->resetTime - \microtime(true)).' seconds');
		}
		
		/*
        $this->limit = $limit ?? $this->limit;
        $this->remaining = $remaining ?? $this->remaining;
        $this->resetTime = $resetTime ?? $this->resetTime;
		*/
        
        if ($this->remaining === 0 && $this->resetTime > \microtime(true)) {
            $this->api->client->emit('debug', 'Endpoint "'.$this->endpoint.'" and Bucket "'.$bucketHeader.'" ratelimit encountered, continuing in '.($this->resetTime - \microtime(true)).' seconds');
        }
    }
    
    /**
     * Returns the endpoint this bucket is for.
     * @return string
	 
     */
    function getEndpoint(): string {
        return $this->endpoint;
    }
    
	/**
     * Returns the endpoint this bucket is for.
     * @return string
     */
    function getBucketHeader(): string {
        return $this->bucketHeader ?? "";
    }
	
    /**
     * Returns the size of the queue.
     * @return int
     */
    function size(): int {
        return \count($this->queue);
    }
    
    /**
     * Pushes a new request into the queue.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $request
     * @return $this
     */
    function push(\CharlotteDunois\Yasmin\HTTP\APIRequest $request) {
        $this->queue[] = $request;
        return $this;
    }
    
    /**
     * Unshifts a new request into the queue. Modifies remaining ratelimit.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $request
     * @return $this
     */
	function unshift(\CharlotteDunois\Yasmin\HTTP\APIRequest $request) {
		\array_unshift($this->queue, $request);
		$this->remaining++;
		return $this;
	}
    
    /**
     * Retrieves ratelimit meta data.
     *
     * The resolved value must be:
     * ```
     * array(
     *     'limited' => bool,
     *     'resetTime' => int|null
     * )
     * ```
     *
     * @return \React\Promise\ExtendedPromiseInterface|array
     */
    function getMeta() {
        if ($this->resetTime && \microtime(true) > $this->resetTime) {
            $this->resetTime = null;
            $this->remaining = ($this->limit ? $this->limit : \INF);
            
            $limited = false;
        } else {
            $limited = ($this->limit !== 0 && $this->remaining === 0);
        }
        
        return array('limited' => $limited, 'resetTime' => $this->resetTime, 'bucketHeader' => $this->bucketHeader);
    }
    
    /**
     * Returns the first queue item or false. Modifies remaining ratelimit.
     * @return \CharlotteDunois\Yasmin\HTTP\APIRequest|false
     */
    function shift() {
        if (\count($this->queue) === 0) {
            return false;
        }
        
        $item = \array_shift($this->queue);
        $this->remaining--;
        
        return $item;
    }
    
    /**
     * Unsets all queue items.
     * @return void
     */
    function clear(): void {
        $this->remaining = 0;
        while ($item = \array_shift($this->queue)) {
            unset($item);
        }
    }
}

abstract class AbstractRatelimitBucket{
	function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api, string $endpoint, ?string $bucketHeader = null) {
		$this->api = $api;
		$this->endpoint = $endpoint;
		$this->bucketHeader = $bucketHeader;
	}
	function handleRatelimit(?int $limit, ?int $remaining, ?float $resetTime) {
        if ($limit === null && $remaining === null && $resetTime === null) {
            $this->remaining++; // there is no ratelimit...
            return;
        }
        
        $this->limit = $limit ?? $this->limit;
        $this->remaining = $remaining ?? $this->remaining;
        $this->resetTime = $resetTime ?? $this->resetTime;
        
        if ($this->remaining === 0 && $this->resetTime > \microtime(true)) {
            $this->api->client->emit('debug', 'Endpoint "'.$this->endpoint.'" and Bucket "'.$this->bucketHeader.'" ratelimit encountered, continuing in '.($this->resetTime - \microtime(true)).' seconds');
			echo '[RATELIMITBUCKET XBUCKETS]: ' . $this->bucketHeader . PHP_EOL;
			//$this->client->xBuckets[$ratelimit->getEndpoint()][] = $this->bucketHeader;
        }
    }
}
