<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Utils;

/**
 * URL Helper methods.
 */
class URLHelpers {
    private static $handler;
    private static $http;
    private static $loop;
    private static $timer;
    
    /**
     * Sets the Event Loop.
     * @param \React\EventLoop\LoopInterface  $loop
     * @internal
     */
    static function setLoop(\React\EventLoop\LoopInterface $loop) {
        self::$loop = $loop;
    }
    
    /**
     * Sets the Guzzle handler and client.
     * @internal
     */
    private static function setHTTPClient() {
        self::$handler = new \GuzzleHttp\Handler\CurlMultiHandler();
        self::$http = new \GuzzleHttp\Client(array(
            'handler' => \GuzzleHttp\HandlerStack::create(self::$handler)
        ));
    }
    
    /**
     * Returns the Guzzle client.
     */
    static function getHTTPClient() {
        if(!self::$http) {
            self::setHTTPClient();
        }
        
        return self::$http;
    }
    
    /**
     * Sets the Guzzle timer.
     */
    private static function setTimer() {
        if(!self::$timer) {
            self::$timer = self::$loop->addPeriodicTimer(0, \Closure::bind(function () {
                $this->tick();
                
                $queue = \GuzzleHttp\Promise\queue();
                $handles = $this->handles;
                
                if($queue->isEmpty() && \count($handles) === 0) {
                    URLHelpers::stopTimer();
                }
            }, self::$handler, self::$handler));
        }
    }
    
    /**
     * Cancels the Guzzle timer and unsets it.
     */
    static function stopTimer() {
        if(self::$timer) {
            self::$timer->cancel();
            self::$timer = null;
        }
    }
    
    /**
     * Makes an asynchronous request. Resolves with an instance of Response.
     * @param \GuzzleHttp\Psr7\Request  $request
     * @param array|null                $requestOptions
     * @return \GuzzleHttp\Promise\Promise
     * @see \GuzzleHttp\Psr7\Response
     */
    static function makeRequest(\GuzzleHttp\Psr7\Request $request, ?array $requestOptions = null) {
        if(!self::$http) {
            self::setHTTPClient();
        }
        
        self::setTimer();
        
        return self::$http->sendAsync($request, ($requestOptions ?? array()));
    }
    
    /**
     * Makes a synchronous request.
     * @param \GuzzleHttp\Psr7\Request  $request
     * @param array|null                $requestOptions
     * @return \GuzzleHttp\Psr7\Response
     */
    static function makeRequestSync(\GuzzleHttp\Psr7\Request $request, ?array $requestOptions = null) {
        if(!self::$http) {
            self::setHTTPClient();
        }
        
        return self::$http->send($request, ($requestOptions ?? array()));
    }
    
    /**
     * Asynchronously resolves a given URL to the response body. Resolves with a string.
     * @param string      $url
     * @param array|null  $requestHeaders
     * @return \React\Promise\Promise
     */
    static function resolveURLToData(string $url, ?array $requestHeaders = null) {
        if(!self::$http) {
            self::setHTTPClient();
        }
        
        self::setTimer();
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($url, $requestHeaders) {
            if($requestHeaders === null) {
                $requestHeaders = array();
            }
            
            foreach($requestHeaders as $key => $val) {
                unset($requestHeaders[$key]);
                $nkey = \ucwords($key, '-');
                $requestHeaders[$nkey] = $val;
            }
            
            if(empty($requestHeaders['User-Agent'])) {
                $requestHeaders['User-Agent'] = \CharlotteDunois\Yasmin\Constants::DEFAULT_USER_AGENT;
            }
            
            $request = new \GuzzleHttp\Psr7\Request('GET', $url, $requestHeaders);
            
            self::$http->sendAsync($request)->then(function ($response) use ($resolve) {
                $resolve((string) $response->getBody());
            }, $reject);
        }));
    }
}
