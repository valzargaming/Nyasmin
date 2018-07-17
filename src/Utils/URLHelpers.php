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
    /**
     * The default HTTP user agent.
     * @var string
     * @internal
     */
    const DEFAULT_USER_AGENT = 'Yasmin (https://github.com/CharlotteDunois/Yasmin)';
    
    /** @var \GuzzleHttp\Handler\CurlMultiHandler */
    private static $handler;
    
    /** @var \GuzzleHttp\Client */
    private static $http;
    
    /** @var \React\EventLoop\LoopInterface */
    private static $loop;
    
    /** @var \React\EventLoop\TimerInterface|\React\EventLoop\Timer\TimerInterface */
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
                    URLHelpers::destroy();
                }
            }, self::$handler, self::$handler));
        }
    }
    
    /**
     * Cancels the Guzzle timer and unsets it.
     */
    static function destroy() {
        if(self::$timer) {
            self::$loop->cancelTimer(self::$timer);
            self::$timer = null;
        }
    }
    
    /**
     * Makes an asynchronous request. Resolves with an instance of ResponseInterface.
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param array|null                          $requestOptions
     * @return \React\Promise\ExtendedPromiseInterface
     * @see \Psr\Http\Message\ResponseInterface
     */
    static function makeRequest(\Psr\Http\Message\RequestInterface $request, ?array $requestOptions = null) {
        if(!self::$http) {
            self::setHTTPClient();
        }
        
        self::setTimer();
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use (&$request, &$requestOptions) {
            self::$http->sendAsync($request, ($requestOptions ?? array()))->then($resolve, $reject);
        }));
    }
    
    /**
     * Makes a synchronous request.
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param array|null                          $requestOptions
     * @return \Psr\Http\Message\ResponseInterface
     */
    static function makeRequestSync(\Psr\Http\Message\RequestInterface $request, ?array $requestOptions = null) {
        if(!self::$http) {
            self::setHTTPClient();
        }
        
        return self::$http->send($request, ($requestOptions ?? array()));
    }
    
    /**
     * Asynchronously resolves a given URL to the response body. Resolves with a string.
     * @param string      $url
     * @param array|null  $requestHeaders
     * @return \React\Promise\ExtendedPromiseInterface
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
                $requestHeaders['User-Agent'] = self::DEFAULT_USER_AGENT;
            }
            
            $request = new \GuzzleHttp\Psr7\Request('GET', $url, $requestHeaders);
            
            self::$http->sendAsync($request)->then(function ($response) use ($resolve) {
                $body = (string) $response->getBody();
                $resolve($body);
            }, $reject);
        }));
    }
}
