<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
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
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    
    /**
     * @var \GuzzleHttp\Handler\CurlMultiHandler
     */
    protected $handler;
    
    /**
     * @var \GuzzleHttp\Client
     */
    protected $http;
    
    /**
     * @var \React\EventLoop\Timer\Timer
     */
    protected $timer;
    
    /**
     * @var array
     */
    protected $ratelimits = array();
    
    /**
     * Are we globally ratelimited?
     * @var bool
     */
    protected $limited = false;
    
    /**
     * When can we send noodz again?
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
        $this->loop = $this->client->getLoop();
        
        $this->addToRateLimit('global');
        
        $this->handler = new \GuzzleHttp\Handler\CurlMultiHandler();
        $this->http = new \GuzzleHttp\Client(array(
            'handler' => \GuzzleHttp\HandlerStack::create($this->handler)
        ));
    }
    
    function __destruct() {
        $this->limited = true;
        $this->resetTime = 0;
        $this->stopTimer();
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
        return new \React\Promise\Promise(function (callable $resolve, $reject) use ($apirequest) {
            $apirequest->resolve = $resolve;
            $apirequest->reject = $reject;
            
            $this->queue[] = $apirequest;
            $this->processQueue();
        }, function () use ($apirequest) {
            $index = \array_search($apirequest, $this->queue, true);
            if($index !== false) {
                unset($this->queue[$index]);
                return;
            }
            
            throw new \Exception('Can not cancel request once it is getting processed');
        });
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
        
        return new \React\Promise\Promise(function (callable $resolve, $reject) use ($gateway) {
            try {
                $request = $gateway->request();
                $response = $this->http->send($request);
                
                $status = $response->getStatusCode();
                $body = \json_decode($response->getBody(), true);
                
                if($status >= 400) {
                    $error = new \Exception($response->getReasonPhrase());
                    return $reject($error);
                }
                
                $resolve($body);
            } catch(\Exception $e) {
                $reject($e);
            }
        });
    }
    
    /**
     * Cancels the Guzzle timer and unsets it.
     */
    function stopTimer() {
        if($this->timer) {
            $this->timer->cancel();
            $this->timer = null;
        }
    }
    
    /**
     * Processes the queue on next tick.
     */
    private function process() {
        $this->loop->futureTick(function () {
            $this->_process();
        });
    }
    
    /**
     * Processes the queue.
     */
    private function _process() {
        if(\count($this->queue) === 0) {
            $this->running = false;
            return;
        }
        
        if($this->limited === true && \time() > $this->resetTime) {
            $this->process();
            return;
        }
        
        $item = \array_shift($this->queue);
        if(!$item) {
            $this->running = false;
            return;
        }
        
        $endpoint = $this->getRatelimitEndpoint($item->getEndpoint());
        if(!empty($endpoint) && empty($this->ratelimits[$endpoint])) {
            $this->addToRateLimit($endpoint);
        }
        
        $ratelimit = &$this->ratelimits[$endpoint];
        if($ratelimit['remaining'] === 0 && $ratelimit['reset'] < \time()) {
            // Shift the item back into queue as first item after the ratelimit got resetted
            $this->loop->addTimer((\time() - $ratelimit['reset']), function () use ($item) {
                \array_unshift($this->queue, $item);
            });
            
            $this->process();
            return;
        }
        
        if(!$this->timer) {
            $class = $this;
            $this->timer = $this->loop->addPeriodicTimer(0, \Closure::bind(function () use ($class) {
                $this->tick();
                
                if(empty($this->handles) && \GuzzleHttp\Promise\queue()->isEmpty()) {
                    $class->stopTimer();
                }
            }, $this->handler, $this->handler));
        }
        
        $this->execute($item);
    }
    
    /**
     * Executes an API Request.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $item
     */
    private function execute(\CharlotteDunois\Yasmin\HTTP\APIRequest $item) {
        $endpoint = $this->getRatelimitEndpoint($item->getEndpoint());
        $ratelimit = &$this->ratelimits[$endpoint];
        
        $request = $item->request();
        $this->http->sendAsync($request)->then(function ($response) {
            return $response;
        }, function ($error) use ($item) {
            if($error->hasResponse()) {
                return $error->getResponse();
            }
            
            $item->reject($error->getMessage());
            return null;
        })->then(function ($response) use ($item, $ratelimit) {
            if(!$response) {
                return;
            }
            
            if($response->hasHeader('X-RateLimit-Limit')) {
                $dateDiff = 0;
                if($response->hasHeader('Date')) {
                    $dateDiff = \time() - ((new \DateTime($response->getHeader('Date')))->format('U'));
                }
                
                $ratelimit['limit'] = ((int) $response->getHeader('X-RateLimit-Limit'));
                $ratelimit['remaining'] = ((int) $response->getHeader('X-RateLimit-Remaining'));
                $ratelimit['reset'] = ((int) $response->getHeader('X-RateLimit-Reset')) + $dateDiff;
            }
            
            if($response->hasHeader('X-RateLimit-Global')) {
                $this->limited = true;
                $this->resetTime = $ratelimit['reset'];
            }
            
            $status = $response->getStatusCode();
            
            if($status === 204) {
                $item->resolve(); /** @scrutinizer ignore-call */
                $this->process();
                return;
            }
            
            $body = \json_decode($response->getBody(), true);
            
            if($status >= 400) {
                if($status === 429) {
                    \array_unshift($this->queue, $item);
                    $this->process();
                    return;
                }
                
                if($status >= 500) {
                    $error = new \Exception($response->getReasonPhrase());
                } else {
                    $error = new \CharlotteDunois\Yasmin\HTTP\DiscordAPIError($item->getEndpoint(), $body);
                }
                
                return $item->reject($error); /** @scrutinizer ignore-call */
            }
            
            $item->resolve($body); /** @scrutinizer ignore-call */
            $this->process();
        }, function ($error) {
            $this->client->emit('error', $error);
            return null;
        });
    }
    
    /**
     * Adds a ratelimit bucket to the bucket.
     * @param string $endpoint
     */
    private function addToRateLimit(string $endpoint) {
        $this->ratelimits[$endpoint] = array(
            'limit' => 0,
            'remaining' => \INF,
            'reset' => \INF
        );
    }
}
