<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\Utils;

/**
 * Helper methods.
 */
class URLHelpers {
    static private $handler;
    static private $http;
    static private $loop;
    static private $timer;
    
    static function setLoop(\React\EventLoop\LoopInterface $loop) {
        self::$loop = $loop;
    }
    
    static private function setHTTPClient() {
        self::$handler = new \GuzzleHttp\Handler\CurlMultiHandler();
        self::$http = new \GuzzleHttp\Client(array(
            'handler' => \GuzzleHttp\HandlerStack::create(self::$handler)
        ));
    }
    
    /**
     * Sets the Guzzle timer.
     */
    static private function setTimer() {
        if(!self::$timer) {
            self::$timer = self::$loop->addPeriodicTimer(0, \Closure::bind(function () {
                $this->tick();
                
                try {
                    if(empty($this->handles) && \GuzzleHttp\Promise\queue()->isEmpty()) {
                        URLHelpers::stopTimer();
                    }
                } catch(\BadMethodCallException $e) {
                    URLHelpers::stopTimer();
                }
            }, self::$handler, self::$handler), true);
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
    
    static function resolveURLToData($url) {
        if(!self::$http) {
            self::setHTTPClient();
        }
        
        self::setTimer();
        
        return new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($url) {
            $request = new \GuzzleHttp\Psr7\Request('GET', $url);
            
            self::$http->sendAsync($request)->then(function ($response) use ($resolve) {
                $resolve($response->getBody());
            }, $reject);
        });
    }
}
