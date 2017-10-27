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
 * @access private
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
            $apirequest->deferred->promise()->then($resolve, $reject);
            
            $endpoint = $this->getRatelimitEndpoint($apirequest->getEndpoint());
            if(!empty($endpoint)) {
                $this->client->emit('debug', 'Adding request "'.$apirequest->getEndpoint().'" to ratelimit bucket');
                $bucket = $this->getRatelimitBucket($endpoint);
                $bucket->push($apirequest);
                $this->queue[] = $bucket;
            } else {
                $this->client->emit('debug', 'Adding request "'.$apirequest->getEndpoint().'" to global queue');
                $this->queue[] = $apirequest;
            }
            
            $this->processQueue();
        }));
    }
    
    /**
     * Starts the queue.
     */
    function processQueue() {
        if($this->running === true) {
            return;
        }
        
        $this->running = true;
        $this->process();
    }
    
    /**
     * Returns the Authorization header.
     * @return string;
     */
    function getAuthorization() {
        if(empty($this->client->token)) {
            throw new \Exception('Can not make a HTTP request without a token');
        }
        
        $user = $this->client->getClientUser();
        if($user && $user->bot === true) {
            return 'Bot '.$this->client->token;
        }
        
        return $this->client->token;
    }
    
    /**
     * Turns an endpoint path to the ratelimit path.
     * @param string $endpoint
     * @return string
     */
    function getRatelimitEndpoint(string $endpoint) {
        \preg_match('/((?:.*?)\/(?:\d+))/', $endpoint, $matches);
        if($matches && $matches[1]) {
            return $matches[1];
        }
        
        $pos = (int) strpos($endpoint, '/');
        if($pos > 0) {
            return \substr($endpoint, 0, $pos);
        }
        
        return $endpoint;
    }
    
    /**
     * Gets the Gateway from the Discord API.
     * @param bool $bot Should we use the bot endpoint?
     */
    function getGateway($bot = false) {
        $gateway = new \CharlotteDunois\Yasmin\HTTP\APIRequest($this, 'GET', 'gateway'.($bot ? '/bot' : ''), array());
        return $this->add($gateway);
    }
    
    /**
     * Gets the Gateway from the Discord API synchronously.
     * @param bool $bot Should we use the bot endpoint?
     */
    function getGatewaySync($bot = false) {
        $gateway = new \CharlotteDunois\Yasmin\HTTP\APIRequest($this, 'GET', 'gateway'.($bot ? '/bot' : ''), array());
        
        return (new \React\Promise\Promise(function (callable $resolve, $reject) use ($gateway) {
            try {
                $request = $gateway->request();
                $response = \CharlotteDunois\Yasmin\Utils\URLHelpers::makeRequestSync($request, $request->requestOptions);
                
                $status = $response->getStatusCode();
                $body = \json_decode($response->getBody(), true);
                
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
     * Processes the queue on next tick.
     */
    private function process() {
        $this->loop->futureTick(function () {
            $this->client->emit('debug', 'Starting API Manager queue');
            $this->_process();
        });
    }
    
    /**
     * Processes the queue.
     */
    private function _process(bool $executeContext = false) {
        if($executeContext) {
            $offset = (int) $this->client->getOption('http.restTimeOffset', 0);
            if($offset > 0) {
                $offset = $offset / 1000;
                
                $this->client->addTimer($offset, function () {
                    $this->_process();
                }, true);
                
                return;
            }
        }
        
        if($this->limited === true) {
            if(\time() < $this->resetTime) {
                $this->client->emit('debug', 'We are API-wise globally ratelimited');
                
                $this->client->addTimer((\time() - $this->resetTime), function () {
                    $this->_process();
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
        
        if($item instanceof \CharlotteDunois\Yasmin\HTTP\RatelimitBucket) {
            if($item->size() > 0 && $item->limited() === false) {
                $this->client->emit('debug', 'Retrieved item from bucket "'.$item->getEndpoint().'"');
                $item = $item->shift();
            } else {
                if($item->size() > 0) {
                    $this->queue[] = $item;
                }
                
                $this->_process();
                return;
            }
        }
        
        if(!$item) {
            $this->_process();
            return;
        }
        
        $this->execute($item);
    }
    
    /**
     * Executes an API Request.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $item
     */
    private function execute(\CharlotteDunois\Yasmin\HTTP\APIRequest $item) {
        $endpoint = $this->getRatelimitEndpoint($item->getEndpoint());
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
                $this->_process(true);
                return;
            }
            
            try {
                $status = $response->getStatusCode();
                $this->client->emit('debug', 'Got response for item "'.$item->getEndpoint().'" with HTTP status code '.$status);
                
                if($response->hasHeader('X-RateLimit-Global')) {
                    $this->handleRatelimit($response);
                } elseif($ratelimit !== null) {
                    $ratelimit->handleRatelimit($response);
                }
                
                if($status === 204) {
                    $item->deferred->resolve();
                    $this->_process(true);
                    return;
                }
                
                $body = \json_decode($response->getBody(), true);
                
                if($status >= 400) {
                    if($status === 429 || ($status >= 500 && $status < 600)) {
                        $this->client->emit('debug', 'Unshifting item "'.$item->getEndpoint().'" due to HTTP '.$status);
                        
                        if($ratelimit !== null) {
                            $ratelimit->unshift($item);
                        } else {
                            \array_unshift($this->queue, $item);
                        }
                        
                        $this->_process(true);
                        return;
                    }
                    
                    if($status >= 400 && $status < 500) {
                        $error = new \CharlotteDunois\Yasmin\HTTP\DiscordAPIError($item->getEndpoint(), $body);
                    } else {
                        $error = new \Exception($response->getReasonPhrase());
                    }
                    
                    throw $error;
                }
                
                $item->deferred->resolve($body);
                $this->_process(true);
            } catch(\Exception $e) {
                $item->deferred->reject($e);
                $this->_process(true);
            }
        });
    }
    
    /**
     * Gets the ratelimit bucket for the specific endpoint.
     * @param string $endpoint
     * @return \CharlotteDunois\Yasmin\HTTP\RatelimitBucket
     */
    private function getRatelimitBucket(string $endpoint) {
        if(empty($this->ratelimits[$endpoint])) {
            $this->ratelimits[$endpoint] = new \CharlotteDunois\Yasmin\HTTP\RatelimitBucket($this, $endpoint);
        }
        
        return $this->ratelimits[$endpoint];
    }
    
    /**
     * Handles ratelimit headers.
     * @param \GuzzleHttp\Psr7\Response $response
     */
    private function handleRatelimit(\GuzzleHttp\Psr7\Response $response) {
        $dateDiff = \time() - ((new \DateTime($response->getHeader('Date')[0]))->getTimestamp());
        
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
    }
}
