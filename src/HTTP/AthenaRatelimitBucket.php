<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP;

/**
 * Manages a route's ratelimit in Redis, using Athena. Requires client option `http.ratelimitbucket.athena` to be set to an instance of `AthenaCache`.
 *
 * Requires the suggested package `charlottedunois/athena`.
 * @internal
 */
final class AthenaRatelimitBucket implements \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface {
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     */
    protected $api;
    
    /**
     * @var string
     */
    protected $endpoint;
    
    /**
     * @var array
     */
    protected $queue;
    
    /**
     * @var \CharlotteDunois\Athena\AthenaCache
     */
    protected $cache;
    
    /**
     * @var bool
     */
    protected $busy = false;
    
    /**
     * DO NOT initialize this class yourself.
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager  $api
     * @param string                                   $endpoint
     * @throws \RuntimeException
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api, string $endpoint) {
        $this->api = $api;
        $this->endpoint = $endpoint;
        
        $this->cache = $this->api->client->getOption('http.ratelimitbucket.athena');
        if(!($this->cache instanceof \CharlotteDunois\Athena\AthenaCache)) {
            throw new \RuntimeException('No Athena Cache instance set for Athena Ratelimit Bucket');
        }
    }
    
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
     * Sets the ratelimits from the response
     * @param int|null  $limit
     * @param int|null  $remaining
     * @param int|null  $resetTime
     * @return \React\Promise\ExtendedPromiseInterface|void
     */
    function handleRatelimit(?int $limit, ?int $remaining, ?int $resetTime) {
        return $this->get()->then(function ($data) use ($limit, $remaining, $resetTime) {
            if($limit === null && $remaining === null && $resetTime === null) {
                $limit = $data['limit'];
                $remaining = $data['remaining'] + 1; // there is no ratelimit...
                $resetTime = $data['resetTime'];
            } else {
                $limit = $limit ?? $data['limit'];
                $remaining = $remaining ?? $data['remaining'];
                $resetTime = $resetTime ?? $data['resetTime'];
            }
            
            if($remaining === 0 && $resetTime > \time()) {
                $this->api->client->emit('debug', 'Endpoint "'.$this->endpoint.'" ratelimit encountered, continueing in '.($resetTime - \time()).' seconds');
            }
            
            return $this->cache->set('yasmin-ratelimiter-'.$this->endpoint, array('limit' => $limit, 'remaining' => $remaining, 'resetTime' => $resetTime));
        });
    }
    
    /**
     * Returns the endpoint this bucket is for.
     * @return string
     */
    function getEndpoint(): string {
        return $this->endpoint;
    }
    
    /**
     * Returns the size of the queue
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
        
        $this->get()->then(function ($data) {
            $data['remaining']++;
            
            return $this->cache->set('yasmin-ratelimiter-'.$this->endpoint, $data);
        })->done(null, array($this->api->client, 'handlePromiseRejection'));
        
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
        return $this->get()->then(function ($data) {
            if($data['resetTime'] && \time() > $data['resetTime']) {
                $data['resetTime'] = null;
                $limited = false;
            } else {
                $limited = ($data['limit'] !== 0 && $data['remaining'] === 0);
            }
            
            return array('limited' => $limited, 'resetTime' => $data['resetTime']);
        });
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
        
        $this->get()->then(function ($data) {
            $data['remaining']--;
            
            return $this->cache->set('yasmin-ratelimiter-'.$this->endpoint, $data);
        })->done(null, array($this->api->client, 'handlePromiseRejection'));
        
        return $item;
    }
    
    /**
     * Unsets all queue items.
     * @return void
     */
    function clear(): void {
        $queue = $this->queue;
        $this->queue = array();
        
        while($item = \array_shift($queue)) {
            unset($item);
        }
    }
    
    /**
     * Retrieves the cache data.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    protected function get() {
        return $this->cache->get('yasmin-ratelimiter-'.$this->endpoint, array('limit' => 0, 'remaining' => 0, 'resetTime' => null));
    }
}
