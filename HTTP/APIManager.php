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
 * Handles the API.
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
     * Determines whether we're processing the queue or not.
     * @var bool
     */
    protected $running = false;
    
    /**
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
    
    /**
     * @property-read \CharlotteDunois\Yasmin\HTTP\APIEndpoints  $endpoints  The class with the endpoints.
     */
    function __get($name) {
        switch($name) {
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
     * Makes an API request,
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
     * Adds an APIRequest to the queue.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $apirequest
     * @return \React\Promise\Promise
     */
    function add(\CharlotteDunois\Yasmin\HTTP\APIRequest $apirequest) {
        return (new \React\Promise\Promise(function (callable $resolve, $reject) use ($apirequest) {
            $apirequest->deferred = new \React\Promise\Deferred();
            $apirequest->deferred->promise()->then($resolve, $reject)->done(null, array($this->client, 'handlePromiseRejection'));
            
            $endpoint = $this->getRatelimitEndpoint($apirequest->getEndpoint(), $apirequest);
            if(!empty($endpoint)) {
                $this->client->emit('debug', 'Adding request "'.$apirequest->getEndpoint().'" to ratelimit bucket');
                $bucket = $this->getRatelimitBucket($endpoint);
                $bucket->push($apirequest);
                $this->queue[] = $bucket;
            } else {
                $this->client->emit('debug', 'Adding request "'.$apirequest->getEndpoint().'" to global queue');
                $this->queue[] = $apirequest;
            }
            
            $this->startQueue();
        }));
    }
    
    /**
     * Starts the queue.
     */
    function startQueue() {
        if($this->running === true) {
            return;
        }
        
        $this->running = true;
        $this->processFuture();
    }
    
    /**
     * Returns the Authorization header value.
     * @return string
     */
    function getAuthorization() {
        if(empty($this->client->token)) {
            throw new \Exception('Can not make a HTTP request without a token');
        }
        
        return 'Bot '.$this->client->token;
    }
    
    /**
     * Gets the Gateway from the Discord API.
     * @param bool $bot Should we use the bot endpoint?
     */
    function getGateway(bool $bot = false) {
        $gateway = new \CharlotteDunois\Yasmin\HTTP\APIRequest($this, 'GET', 'gateway'.($bot ? '/bot' : ''), array());
        return $this->add($gateway);
    }
    
    /**
     * Gets the Gateway from the Discord API synchronously.
     * @param bool $bot Should we use the bot endpoint?
     * @return \React\Promise\Promise
     */
    function getGatewaySync(bool $bot = false) {
        $gateway = new \CharlotteDunois\Yasmin\HTTP\APIRequest($this, 'GET', 'gateway'.($bot ? '/bot' : ''), array());
        
        return (new \React\Promise\Promise(function (callable $resolve, $reject) use ($gateway) {
            try {
                $request = $gateway->request();
                $response = \CharlotteDunois\Yasmin\Utils\URLHelpers::makeRequestSync($request, $request->requestOptions);
                
                $status = $response->getStatusCode();
                $body = $this->decodeBody($response);
                
                if($status >= 300) {
                    $error = new \Exception($response->getReasonPhrase());
                    return $reject($error);
                }
                
                $resolve($body);
            } catch(\Exception $e) {
                $reject($e);
            }
        }));
    }
    
    /**
     * Processes the queue on future tick.
     */
    protected function processFuture() {
        $this->loop->futureTick(function () {
            $this->client->emit('debug', 'Starting API Manager queue');
            $this->process();
        });
    }
    
    /**
     * Processes the queue delayed, depends on rest time offset.
     */
    protected function processDelayed() {
        $offset = (int) $this->client->getOption('http.restTimeOffset', 0);
        if($offset > 0) {
            $offset = $offset / 1000;
            
            $this->client->addTimer($offset, function () {
                $this->process();
            }, true);
            
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
                $this->client->emit('debug', 'We are API-wise globally ratelimited');
                
                $this->client->addTimer(($this->resetTime + 1 - \time()), function () {
                    $this->process();
                }, true);
                
                return;
            }
            
            $this->limited = false;
            $this->remaining = ($this->limit ? $this->limit : \INF);
        }
        
        if(\count($this->queue) === 0) {
            $this->client->emit('debug', 'No items in queue, ending API manager queue');
            
            $this->running = false;
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
            if($item->size() > 0 && $item->limited() === false) {
                $this->client->emit('debug', 'Retrieved item from bucket "'.$item->getEndpoint().'"');
                $item = $item->shift();
            } else {
                if($item->size() > 0) {
                    $this->queue[] = $item;
                }
                
                $continue = $this->handleQueueTiming();
                if($continue) {
                    $this->process();
                }
                
                return;
            }
        }
        
        if(!$item) {
            $this->process();
            return;
        }
        
        $this->execute($item);
    }
    
    /**
     * Executes an API Request.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest  $item
     */
    protected function execute(\CharlotteDunois\Yasmin\HTTP\APIRequest $item) {
        $endpoint = $this->getRatelimitEndpoint($item->getEndpoint(), $item);
        $ratelimit = null;
        
        if(!empty($endpoint)) {
            $ratelimit = $this->getRatelimitBucket($endpoint);
        }
        
        $this->client->emit('debug', 'Executing item "'.$item->getEndpoint().'"');
        
        $request = $item->request();
        \CharlotteDunois\Yasmin\Utils\URLHelpers::makeRequest($request, $request->requestOptions)->then(function ($response) {
            return $response;
        }, function ($error) use ($item) {
            if($error->hasResponse()) {
                return $error->getResponse();
            }
            
            $item->deferred->reject($error->getMessage());
            return null;
        })->then(function ($response) use ($item, $ratelimit) {
            if(!$response) {
                $this->processDelayed();
                return;
            }
            
            $this->handleAPIResponse($response, $item, $ratelimit);
        })->then(null, array($this->client, 'handlePromiseRejection'));
    }
    
    /**
     * Gets the response body from the response.
     * @param \GuzzleHttp\Psr7\Response  $response
     * @return mixed
     */
    protected function decodeBody(\GuzzleHttp\Psr7\Response $response) {
        $body = $response->getBody();
        if($body instanceof \GuzzleHttp\Psr7\Stream) {
            $body = $body->getContents();
        }
        
        $json = \json_decode($body, true);
        if(!$json) {
            throw new \Exception('Invalid API response: '.\json_last_error_msg());
        }
        
        return $json;
    }
    
    /**
     * Turns an endpoint path to the ratelimit path.
     * @param string $endpoint
     * @return string
     */
    function getRatelimitEndpoint(string $endpoint, \CharlotteDunois\Yasmin\HTTP\APIRequest $request = null) {
        \preg_match('/((?:.*?)\/(?:\d+)(?:\/messages\/bulk(?:-|_)delete){0,1})/', $endpoint, $matches);
        if(!empty($matches) && !empty($matches[1])) {
            if($request !== null && $request->getMethod() === 'DELETE' && \preg_match('/channels\/(\d+)\/messages\/(\d+)/i', $endpoint) === 1) {
                $matches[1] = 'delete@'.$matches[1];
            }
            
            return $matches[1];
        }
        
        $pos = (int) \strpos($endpoint, '/');
        if($pos > 0) {
            return \substr($endpoint, 0, $pos);
        }
        
        return $endpoint;
    }
    
    /**
     * Gets the ratelimit bucket for the specific endpoint.
     * @param string $endpoint
     * @return \CharlotteDunois\Yasmin\HTTP\RatelimitBucket
     */
    protected function getRatelimitBucket(string $endpoint) {
        if(empty($this->ratelimits[$endpoint])) {
            $this->ratelimits[$endpoint] = new \CharlotteDunois\Yasmin\HTTP\RatelimitBucket($this, $endpoint);
        }
        
        return $this->ratelimits[$endpoint];
    }
    
    /**
     * Handles ratelimit headers.
     * @param \GuzzleHttp\Psr7\Response                          $response
     * @param \CharlotteDunois\Yasmin\HTTP\RatelimitBucket|null  $ratelimit
     */
    protected function handleRatelimit(\GuzzleHttp\Psr7\Response $response, \CharlotteDunois\Yasmin\HTTP\RatelimitBucket $ratelimit = null) {
        $dateDiff = \time() - ((new \DateTime($response->getHeader('Date')[0]))->getTimestamp());
        $limit = ($response->hasHeader('X-RateLimit-Limit') ? ((int) $response->getHeader('X-RateLimit-Limit')[0]) : null);
        $remaining = ($response->hasHeader('X-RateLimit-Remaining') ? ((int) $response->getHeader('X-RateLimit-Remaining')[0]) : null);
        $resetTime = ($response->hasHeader('Retry-After') ? (\time() + ((int) $response->getHeader('Retry-After')[0])) : ($response->hasHeader('X-RateLimit-Reset') ? (((int) $response->getHeader('X-RateLimit-Reset')[0]) + $dateDiff) : null));
        
        $global = false;
        if($response->hasHeader('X-RateLimit-Global')) {
            $global = true;
            
            if($limit !== null) {
                $this->limit = $limit;
            }
            
            if($remaining !== null) {
                $this->remaining = $remaining;
            }
            
            if($resetTime !== null) {
                $this->resetTime = $resetTime;
            }
        } elseif($ratelimit !== null) {
            $ratelimit->handleRatelimit($limit, $remaining, $resetTime);
        }
        
        $this->client->emit('ratelimit', array(
            'global' => $global,
            'limit' => $limit,
            'remaining' => $remaining,
            'resetTime' => $resetTime
        ));
    }
    
    /**
     * Handles the timing of the queue (ratelimits).
     * @return bool
     */
    protected function handleQueueTiming() {
        $cLimited = $this->getQueueReset();
        
        if($cLimited <= 0 || $cLimited === \INF) {
            return true;
        }
        
        $this->client->emit('debug', 'Pausing API manager queue due to ratelimits');
        
        $this->running = false;
        $this->client->addTimer(($cLimited + 1 - \time()), function () {
            $this->startQueue();
        });
        
        return false;
    }
    
    /**
     * Returns the reset time of the first reseted item.
     */
    protected function getQueueReset() {
        $cLimited = \array_reduce($this->queue, function ($prev, $val) {
            if($val instanceof \CharlotteDunois\Yasmin\HTTP\RatelimitBucket && $val->limited()) {
                $rs = (int) $val->getResetTime();
                if($rs < $prev) {
                    return $rs;
                }
            }
            
            return $prev;
        }, \INF);
        
        if($cLimited === \INF && $this->resetTime) {
            $cLimited = $this->resetTime;
        }
        
        return $cLimited;
    }
    
    /**
     * Handles a response of an API request.
     * @param \GuzzleHttp\Psr7\Response                          $response
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest            $item
     * @param \CharlotteDunois\Yasmin\HTTP\RatelimitBucket|null  $ratelimit
     */
    protected function handleAPIResponse(\GuzzleHttp\Psr7\Response $response, \CharlotteDunois\Yasmin\HTTP\APIRequest $item, \CharlotteDunois\Yasmin\HTTP\RatelimitBucket $ratelimit = null) {
        try {
            $status = $response->getStatusCode();
            $this->client->emit('debug', 'Got response for item "'.$item->getEndpoint().'" with HTTP status code '.$status);
            
            $this->handleRatelimit($response, $ratelimit);
            
            if($status === 204) {
                $item->deferred->resolve();
                $this->processDelayed();
                return;
            }
            
            $body = $this->decodeBody($response);
            
            if($status >= 400) {
                $error = $this->handleAPIError($response, $item, $body, $ratelimit);
                if($error === null) {
                    $this->processDelayed();
                    return;
                }
                
                throw $error;
            }
            
            $item->deferred->resolve($body);
        } catch(\Throwable $e) {
            $item->deferred->reject($e);
        } catch(\Exception $e) {
            $item->deferred->reject($e);
        }
        
        $this->processDelayed();
    }
    
    /**
     * Handles an API error.
     * @param \GuzzleHttp\Psr7\Response                          $response
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest            $item
     * @param mixed                                              $body
     * @param \CharlotteDunois\Yasmin\HTTP\RatelimitBucket|null  $ratelimit
     * @return \CharlotteDunois\Yasmin\HTTP\DiscordAPIException|\Exception|null
     */
    protected function handleAPIError(\GuzzleHttp\Psr7\Response $response, \CharlotteDunois\Yasmin\HTTP\APIRequest $item, $body, \CharlotteDunois\Yasmin\HTTP\RatelimitBucket $ratelimit = null) {
        $status = $response->getStatusCode();
        
        if($status === 429 || $status >= 500) {
            $this->client->emit('debug', 'Unshifting item "'.$item->getEndpoint().'" due to HTTP '.$status);
            
            if($ratelimit !== null) {
                $ratelimit->unshift($item);
            } else {
                \array_unshift($this->queue, $item);
            }
            
            return null;
        }
        
        if($status >= 400 && $status < 500) {
            $error = new \CharlotteDunois\Yasmin\HTTP\DiscordAPIException($item->getEndpoint(), $body);
        } else {
            $error = new \Exception($response->getReasonPhrase());
        }
        
        return $error;
    }
}
