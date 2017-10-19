<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\HTTP;

class APIManager {
    protected $client;
    protected $loop;
    
    protected $handler;
    protected $http;
    
    protected $ratelimits = array();
    protected $limited = false;
    protected $resetTime = 0;
    
    protected $queue = array();
    protected $running = false;
    
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        $this->loop = $this->client->getLoop();
        
        $this->addToRateLimit('global');
        
        $this->handler = new \GuzzleHttp\Handler\CurlMultiHandler();
        $timer = $this->loop->addPeriodicTimer(0, \Closure::bind(function () use (&$timer) {
            $this->tick();
            /*if(empty($this->handles) && Promise\queue()->isEmpty()) {
                $timer->cancel();
            }*/
        }, $this->handler, $this->handler));
        
        $this->http = new \GuzzleHttp\Client(array(
            'handler' => \GuzzleHttp\HandlerStack::create($this->handler)
        ));
    }
    
    function add(\CharlotteDunois\Yasmin\HTTP\APIRequest $apirequest) {
        return \React\Promise\Promise(function (callable $resolve, $reject) use ($apirequest) {
            $apirequest->resolve = $resolve;
            $apirequest->reject = $reject;
            
            $this->queue[] = $apirequest;
            $this->processQueue();
        });
    }
    
    function processQueue() {
        if($this->running === true) {
            return;
        }
        
        $this->running = true;
        $his->process();
    }
    
    function getAuthorization() {
        if(!$this->client->token) {
            throw new \Exception('Can not make a HTTP request without an token');
        }
        
        $user = $this->client->getClientUser();
        if($user && $user->bot === true) {
            return 'Bot '.$this->client->token;
        }
        
        return $this->client->token;
    }
    
    private function endQueue() {
        $this->running = false;
    }
    
    private function process() {
        $this->loop->futureTick(function () {
            $this->execute();
        });
    }
    
    private function execute() {
        if(\count($this->queue) === 0) {
            return $this->endQueue();
        }
        
        if($this->limited === true && \time() > $this->resetTime) {
            return $this->endQueue();
        }
        
        $item = \array_shift($this->queue);
        if(!$item) {
            return $this->endQueue();
        }
        
        $endpoint = $item->getEndpoint();
        if(empty($this->ratelimits[$endpoint])) {
            $this->addToRateLimit($endpoint);
        }
        
        $ratelimit = &$this->ratelimits[$endpoint];
        if($ratelimit['remaining'] === 0 && $ratelimit['reset'] < \time()) {
            // Shift the item back into queue as first item after the ratelimit got resetted
            $this->loop->addTimer((\time() - $ratelimit['reset']), function () {
                \array_unshift($this->queue, $item);
            });
            
            return $this->execute();
        }
        
        $request = $item->request();
        $this->http->sendAsync($request)->then(function ($response) use ($item, $endpoint, $ratelimit) {
            $dateDiff = \time() - ((new \DateTime($response->getHeader('Date')))->format('U'));
            
            $ratelimit['limit'] = ((int) $response->getHeader('X-RateLimit-Limit'));
            $ratelimit['remaining'] = ((int) $response->getHeader('X-RateLimit-Remaining'));
            $ratelimit['reset'] = ((int) $response->getHeader('X-RateLimit-Reset')) + $dateDiff;
            
            if($response->hasHeader('X-RateLimit-Global')) {
                $this->limited = true;
                $this->resetTime = $ratelimit['reset'];
            }
            
            $status = $response->getStatusCode();
            
            if($status === 204) {
                $item->resolve();
                return $this->process();
            }
            
            $body = json_decode($response->getBody(), true);
            
            if($status >= 400) {
                if($status === 429) {
                    \array_unshift($this->queue, $item);
                    return $this->process();
                }
                
                if($status >= 500) {
                    $error = new \Exception($response->getReasonPhrase());
                } else {
                    $error = new \CharlotteDunois\Yasmin\Structures\DiscordAPIError($body);
                }
                
                $item->reject($error);
            }
        });
    }
    
    private function addToRateLimit($endpoint) {
        $this->ratelimits[$endpoint] = array(
            'limit' => 0,
            'remaining' => \INF,
            'reset' => \INF
        );
    }
}
