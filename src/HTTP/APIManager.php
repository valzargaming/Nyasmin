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
 * Handles the API.
 * @property \CharlotteDunois\Yasmin\Client             $client
 * @property \CharlotteDunois\Yasmin\HTTP\APIEndpoints  $endpoints
 * @internal
 */
class APIManager {
    /**
     * @var \CharlotteDunois\Yasmin\Client
     */
    protected $client;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIEndpoints
     */
    protected $endpoints;
    
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\RatelimitBucket[]
     */
    protected $ratelimits = array();
    
    /**
     * Are we globally ratelimited?
     * @var bool
     */
    protected $limited = false;
    
    /**
     * Global rate limit limit.
     * @var int
     */
    protected $limit = 0;
    
    /**
     * Global rate limit remaining.
     * @var int
     */
    protected $remaining = \INF;
    
    /**
     * When can we send again?
     * @var int
     */
    protected $resetTime = 0;
    
    /**
     * The queue for our API requests.
     * @var array
     */
    protected $queue = array();
    
    /**
     * Running buckets we are waiting for a response.
     * @var array
     */
    protected $runningBuckets = array();
    
    /**
     * DO NOT initialize this class yourself.
     * @param \CharlotteDunois\Yasmin\Client $client
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        $this->endpoints = new \CharlotteDunois\Yasmin\HTTP\APIEndpoints($this);
        
        $this->loop = $this->client->getLoop();
    }
    
    function __destruct() {
        $this->destroy();
    }
    
    function __get($name) {
        switch($name) {
            case 'client':
                return $this->client;
            break;
            case 'endpoints':
                return $this->endpoints;
            break;
        }
        
        return null;
    }
    
    /**
     * Clears all buckets and the queue.
     */
    function destroy() {
        $this->limited = true;
        $this->resetTime = \INF;
        
        while($item = \array_shift($this->queue)) {
            if(!($item instanceof \CharlotteDunois\Yasmin\HTTP\RatelimitBucket)) {
                unset($item);
            }
        }
        
        while($bucket = \array_shift($this->ratelimits)) {
            $bucket->clear();
            unset($bucket);
        }
        
        $this->limited = false;
        $this->resetTime = 0;
    }
    
    /**
     * Makes an API request.
     * @param string  $method
     * @param string  $endpoint
     * @param array   $options
     * @return \React\Promise\Promise
     */
    function makeRequest(string $method, string $endpoint, array $options) {
        $request = new \CharlotteDunois\Yasmin\HTTP\APIRequest($this, $method, $endpoint, $options);
        return $this->add($request);
    }
    
    /**
     * Makes an API request synchronously
     * @param string  $method
     * @param string  $endpoint
     * @param array   $options
     * @return \React\Promise\Promise
     */
    function makeRequestSync(string $method, string $endpoint, array $options) {
        $apirequest = new \CharlotteDunois\Yasmin\HTTP\APIRequest($this, $method, $endpoint, $options);
        
        return (new \React\Promise\Promise(function (callable $resolve, $reject) use ($apirequest) {
            try {
                $request = $apirequest->request();
                $response = \CharlotteDunois\Yasmin\Utils\URLHelpers::makeRequestSync($request, $request->requestOptions);
                
                $status = $response->getStatusCode();
                $body = \CharlotteDunois\Yasmin\HTTP\APIRequest::decodeBody($response);
                
                if($status >= 300) {
                    $error = new \Exception($response->getReasonPhrase());
                    return $reject($error);
                }
                
                $resolve($body);
            } catch(\Throwable | \Exception | \Error $e) {
                $reject($e);
            }
        }));
    }
    
    /**
     * Adds an APIRequest to the queue.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $apirequest
     * @return \React\Promise\Promise
     */
    function add(\CharlotteDunois\Yasmin\HTTP\APIRequest $apirequest) {
        return (new \React\Promise\Promise(function (callable $resolve, $reject) use ($apirequest) {
            $apirequest->deferred = new \React\Promise\Deferred();
            $apirequest->deferred->promise()->then($resolve, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            
            $endpoint = $this->getRatelimitEndpoint($apirequest);
            if(!empty($endpoint)) {
                $this->client->emit('debug', 'Adding request "'.$apirequest->getEndpoint().'" to ratelimit bucket');
                $bucket = $this->getRatelimitBucket($endpoint);
                $bucket->push($apirequest);
                $this->queue[] = $bucket;
            } else {
                $this->client->emit('debug', 'Adding request "'.$apirequest->getEndpoint().'" to global queue');
                $this->queue[] = $apirequest;
            }
            
            $this->processFuture();
        }));
    }
    
    /**
     * Unshifts an item into the queue.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest|\CharlotteDunois\Yasmin\HTTP\RatelimitBucket  $item
     */
    function unshiftQueue($item) {
        \array_unshift($this->queue, $item);
    }
    
    /**
     * Gets the Gateway from the Discord API.
     * @param bool  $bot  Should we use the bot endpoint? Requires token.
     */
    final function getGateway(bool $bot = false) {
        return $this->makeRequest('GET', 'gateway'.($bot ? '/bot' : ''), array());
    }
    
    /**
     * Gets the Gateway from the Discord API synchronously.
     * @param bool  $bot  Should we use the bot endpoint? Requires token.
     * @return \React\Promise\Promise
     */
    final function getGatewaySync(bool $bot = false) {
        return $this->makeRequestSync('GET', 'gateway'.($bot ? '/bot' : ''), array());
    }
    
    /**
     * Processes the queue on future tick.
     */
    final protected function processFuture() {
        $this->loop->futureTick(function () {
            $this->process();
        });
    }
    
    /**
     * Processes the queue delayed, depends on rest time offset.
     */
    final protected function processDelayed() {
        $offset = (int) $this->client->getOption('http.restTimeOffset', 0);
        if($offset > 0) {
            $offset = $offset / 1000;
            
            $this->client->addTimer($offset, function () {
                $this->process();
            });
            
            return;
        }
        
        $this->process();
    }
    
    /**
     * Processes the queue.
     */
    protected function process() {
        if($this->limited === true) {
            if(\time() < $this->resetTime) {
                $this->client->addTimer(($this->resetTime + 1 - \time()), function () {
                    $this->process();
                });
                
                return;
            }
            
            $this->limited = false;
            $this->remaining = ($this->limit ? $this->limit : \INF);
        }
        
        if(\count($this->queue) === 0) {
            return;
        }
        
        $item = \array_shift($this->queue);
        $this->processItem($item);
    }
    
    /**
     * Processes a queue item.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest|\CharlotteDunois\Yasmin\HTTP\RatelimitBucket|null  $item
     */
    protected function processItem($item) {
        if($item instanceof \CharlotteDunois\Yasmin\HTTP\RatelimitBucket) {
            if(\in_array($item->getEndpoint(), $this->runningBuckets)) {
                $this->queue[] = $item;
                
                foreach($this->queue as $qitem) {
                    if(!($qitem instanceof \CharlotteDunois\Yasmin\HTTP\RatelimitBucket) || !\in_array($qitem->getEndpoint(), $this->runningBuckets)) {
                        $this->processItem($qitem);
                    }
                }
                
                return;
            }
            
            $item = $this->extractFromBucket($item);
        }
        
        if(!($item instanceof \CharlotteDunois\Yasmin\HTTP\APIRequest)) {
            return;
        }
        
        $this->execute($item);
    }
    
    /**
     * Extracts an item from a ratelimit bucket.
     * @param \CharlotteDunois\Yasmin\HTTP\RatelimitBucket  $item
     * @return \CharlotteDunois\Yasmin\HTTP\APIRequest|bool
     */
    final protected function extractFromBucket(\CharlotteDunois\Yasmin\HTTP\RatelimitBucket $item) {
        if($item->size() > 0) {
            if($item->limited() === false) {
                $this->client->emit('debug', 'Retrieved item from bucket "'.$item->getEndpoint().'"');
                return $item->shift();
            }
            
            $this->queue[] = $item;
        }
        
        $this->client->addTimer(($item->getResetTime() + 1 - \time()), function () {
            $this->process();
        });
        
        return false;
    }
    
    /**
     * Executes an API Request.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest  $item
     */
    protected function execute(\CharlotteDunois\Yasmin\HTTP\APIRequest $item) {
        $endpoint = $this->getRatelimitEndpoint($item);
        $ratelimit = null;
        
        if(!empty($endpoint)) {
            $ratelimit = $this->getRatelimitBucket($endpoint);
            $this->runningBuckets[] = $ratelimit->getEndpoint();
        }
        
        $this->client->emit('debug', 'Executing item "'.$item->getEndpoint().'"');
        
        $item->execute($ratelimit)->then(function ($data) use ($item) {
            if($data === 0) {
                $item->deferred->resolve();
            } elseif($data !== -1) {
                $item->deferred->resolve($data);
            }
        }, function ($error) use ($item) {
            $item->deferred->reject($error);
        })->then(function () use ($ratelimit) {
            $key = \array_search($ratelimit->getEndpoint(), $this->runningBuckets);
            if($key !== false) {
                unset($this->runningBuckets[$key]);
            }
            
            $this->processDelayed();
        }, array($this->client, 'handlePromiseRejection'));
    }
    
    /**
     * Turns an endpoint path to the ratelimit path.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest  $request
     * @return string
     */
    final function getRatelimitEndpoint(\CharlotteDunois\Yasmin\HTTP\APIRequest $request) {
        $endpoint = $request->getEndpoint();
        
        \preg_match('/((?:.*?)\/(?:\d+)(?:\/messages\/((?:bulk(?:-|_)delete)|(?:\d+)){0,1})?)/', $endpoint, $matches);
        if(!empty($matches[1])) {
            if(\is_numeric(($matches[2] ?? null)) && $request->getMethod() === 'DELETE') {
                $matches[1] = 'delete@'.$matches[1];
            }
            
            return $matches[1];
        }
        
        return \substr($endpoint, 0, (\strpos($endpoint, '/') ?: \strlen($endpoint)));
    }
    
    /**
     * Gets the ratelimit bucket for the specific endpoint.
     * @param string $endpoint
     * @return \CharlotteDunois\Yasmin\HTTP\RatelimitBucket
     */
    final protected function getRatelimitBucket(string $endpoint) {
        if(empty($this->ratelimits[$endpoint])) {
            $this->ratelimits[$endpoint] = new \CharlotteDunois\Yasmin\HTTP\RatelimitBucket($this, $endpoint);
        }
        
        return $this->ratelimits[$endpoint];
    }
    
    /**
     * Extracts ratelimits from a response.
     * @param \GuzzleHttp\Psr7\Response  $response
     * @return mixed[]
     */
    final function extractRatelimit(\GuzzleHttp\Psr7\Response $response) {
        $dateDiff = \time() - ((new \DateTime($response->getHeader('Date')[0]))->getTimestamp());
        $limit = ($response->hasHeader('X-RateLimit-Limit') ? ((int) $response->getHeader('X-RateLimit-Limit')[0]) : null);
        $remaining = ($response->hasHeader('X-RateLimit-Remaining') ? ((int) $response->getHeader('X-RateLimit-Remaining')[0]) : null);
        $resetTime = ($response->hasHeader('Retry-After') ? (\time() + ((int) (((int) $response->getHeader('Retry-After')[0]) / 1000))) : ($response->hasHeader('X-RateLimit-Reset') ? (((int) $response->getHeader('X-RateLimit-Reset')[0]) + $dateDiff) : null));
        return \compact('limit', 'remaining', 'resetTime');
    }
    
    /**
     * Handles ratelimits.
     * @param \GuzzleHttp\Psr7\Response                          $response
     * @param \CharlotteDunois\Yasmin\HTTP\RatelimitBucket|null  $ratelimit
     */
    function handleRatelimit(\GuzzleHttp\Psr7\Response $response, ?\CharlotteDunois\Yasmin\HTTP\RatelimitBucket $ratelimit = null) {
        \extract($this->extractRatelimit($response));
        
        $global = false;
        if($response->hasHeader('X-RateLimit-Global')) {
            $global = true;
            
            $this->limit = $limit ?? $this->limit;
            $this->remaining = $remaining ?? $this->remaining;
            $this->resetTime = $resetTime ?? $this->resetTime;
            
            if($this->remaining === 0 && $this->resetTime > \time()) {
                $this->limited = true;
                $this->client->emit('debug', 'Global ratelimit encountered, continueing in '.($this->resetTime - \time()).' seconds');
            } else {
                $this->limited = false;
            }
        } elseif($ratelimit !== null) {
            $ratelimit->handleRatelimit($limit, $remaining, $resetTime);
        }
        
        $this->client->emit('ratelimit', array(
            'endpoint' => ($ratelimit !== null ? $ratelimit->getEndpoint() : 'global'),
            'global' => $global,
            'limit' => $limit,
            'remaining' => $remaining,
            'resetTime' => $resetTime
        ));
    }
}
