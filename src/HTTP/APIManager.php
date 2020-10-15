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
 * Handles the API.
 *
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
     * @var float
     */
    protected $resetTime = 0.0;
    
    /**
     * The queue for our API requests.
     * @var array
     */
    protected $queue = array();
    
    /**
     * The class name of the bucket to use.
     * @var string
     */
    protected $bucketName;
    
    /**
     * Pending promises of buckets setting the ratelimit.
     * @var array
     */
    protected $bucketRatelimitPromises = array();
    
    /**
     * DO NOT initialize this class yourself.
     * @param \CharlotteDunois\Yasmin\Client $client
     */
    function __construct(\CharlotteDunois\Yasmin\Client $client) {
        $this->client = $client;
        $this->endpoints = new \CharlotteDunois\Yasmin\HTTP\APIEndpoints($this);
        
        $this->loop = $this->client->loop;
        
        $this->bucketName = $client->getOption('http.ratelimitbucket.name', \CharlotteDunois\Yasmin\HTTP\RatelimitBucket::class);
    }
    
    /**
     * Default destructor.
     * @internal
     */
    function __destruct() {
        $this->clear();
    }
    
    /**
     * @param string  $name
     * @return bool
     * @throws \Exception
     * @internal
     */
    function __isset($name) {
        try {
            return $this->$name !== null;
        } catch (\RuntimeException $e) {
            if ($e->getTrace()[0]['function'] === '__get') {
                return false;
            }
            
            throw $e;
        }
    }
    
    /**
     * @param string  $name
     * @return mixed
     * @throws \RuntimeException
     * @internal
     */
    function __get($name) {
        switch ($name) {
            case 'client':
                return $this->client;
            break;
            case 'endpoints':
                return $this->endpoints;
            break;
        }
        
        throw new \RuntimeException('Unknown property '.\get_class($this).'::$'.$name);
    }
    
    /**
     * Clears all buckets and the queue.
     * @return void
     */
    function clear() {
        $this->limited = true;
        $this->resetTime = \INF;
        
        while ($item = \array_shift($this->queue)) {
            unset($item);
        }
        
        while ($bucket = \array_shift($this->ratelimits)) {
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
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function makeRequest(string $method, string $endpoint, array $options, ?string $bucketHeader = null) {
        $request = new \CharlotteDunois\Yasmin\HTTP\APIRequest($this, $method, $endpoint, $options, $bucketHeader);
        return $this->add($request);
    }
    
    /**
     * Adds an APIRequest to the queue.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest  $apirequest
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function add(\CharlotteDunois\Yasmin\HTTP\APIRequest $apirequest) {
        return (new \React\Promise\Promise(function(callable $resolve, callable $reject) use ($apirequest) {
            $apirequest->deferred = new \React\Promise\Deferred();
            $apirequest->deferred->promise()->done($resolve, $reject);
            
            $endpoint = $this->getRatelimitEndpoint($apirequest);
			
            if (!empty($endpoint)) {
                $this->client->emit('debug', 'Adding request "'.$apirequest->getEndpoint().'" on bucket "'.$apirequest->getBucketHeader().'" to ratelimit bucket');
                $bucket = $this->getRatelimitBucket($endpoint, $apirequest->getBucketHeader());
                
                $bucket->push($apirequest);
                $this->queue[] = $bucket;
            } else {
                $this->client->emit('debug', 'Adding request "'.$apirequest->getEndpoint().'" on bucket "'.$apirequest->getBucketHeader().'" to global queue');
                $this->queue[] = $apirequest;
            }
            
            $this->processFuture();
        }));
    }
    
    /**
     * Unshifts an item into the queue.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest|\CharlotteDunois\Yasmin\HTTP\RatelimitBucket  $item
     * @return void
     */
    function unshiftQueue($item) {
        \array_unshift($this->queue, $item);
    }
    
    /**
     * Gets the Gateway from the Discord API.
     * @param bool  $bot  Should we use the bot endpoint? Requires token.
     * @return \React\Promise\ExtendedPromiseInterface
     */
    function getGateway(bool $bot = false) {
        return $this->makeRequest('GET', 'gateway'.($bot ? '/bot' : ''), array());
    }
    
    /**
     * Processes the queue on future tick.
     * @return void
     */
    protected function processFuture() {
        $this->loop->futureTick(function() {
            $this->processnew();
        });
    }
    
    /**
     * Processes the queue delayed, depends on rest time offset.
     * @return void
     */
    protected function processDelayed() {
        $offset = (float) $this->client->getOption('http.restTimeOffset', 0.0);
        if ($offset > 0.0) {
            $this->client->addTimer($offset, function() {
                $this->processnew();
            });
            
            return;
        }
        
        $this->processnew();
    }
    
    /**
     * Processes the queue.
     * @return void
     */
    protected function process() { //Process the item in the queue if !$this->limited, otherwise wait until time passes, then send the item to processItem
        if ($this->limited) {
            if (\microtime(true) < $this->resetTime) {
                $this->client->addTimer(($this->resetTime - \microtime(true)), function() {
                    $this->processnew();
                });
                
                return;
            }
            
            $this->limited = false; //Previous time has passed, so we can continue
            $this->remaining = ($this->limit ? $this->limit : \INF);
        }
        
        if (\count($this->queue) === 0) { //Queue is empty
            return;
        }
		
		if (\count($this->queue) >= 500) { //Buckets are not being processed correctly, so clear them out
            $this->queue = array(); 
        }
        
        $item = \array_shift($this->queue); //Remove the item from the queue
        $this->processItemNew($item); //Process the item
    }
	
	/**/
	//DEBUG TODO
	//Picks items from the queue, reads data bout the item and compares with known bucket limits, determines if it should be processed now or later
	protected function processNew() { //Process the item in the queue if !$this->limited then send it to processItem
		echo '[PROCESSNEW] ' . count($this->queue) . PHP_EOL;
		if (\count($this->queue) === 0) { //Queue is empty
            return;
        }
		$item = \array_shift($this->queue); //Remove the item from the queue
		
		$limited = false; //assume that we're not limited
		if ($item instanceof \CharlotteDunois\Yasmin\HTTP\APIRequest){
			if (array_key_exists($item->getEndpoint(), $this->client->xBuckets)){ //Try to get the bucket header for the item
				$bucketlist = array();
				foreach ($this->client->xBuckets[$item->getEndpoint()] as $bucket){
					if ($this->client->xBuckets[$bucket]['limit'] == 0){ //If any of the buckets has a limit of 0,
						$limited = true; //This bucket was recently limited
						$bucketlist[] = $bucket;
					}
				}
			}
		}
		echo "limited: " . $limited . PHP_EOL;
        if ($limited) { //Check against the bucket to see if the time has elapsed
			$elapsed = true;
			foreach ($bucketlist as $bucket){
				if (\microtime(true) < $this->client->xBuckets[$bucket]['resetTime']) { //If it has not elapsed, put it back in the queue and move on to the next item
					echo '[PROCESS PUSH] Not enough time elapsed!' . PHP_EOL;
					$elapsed = false;
				}
			}
			if (!$elapsed){
				array_push($this->queue, $item); //Put it back because we can't work it yet
				/*This doesn't make sense, why hold up the entire queue for one item?
				$this->client->addTimer(($this->resetTime - \microtime(true)), function() {
					$this->processnew();
				});
				*/
				
				//$this->processnew(); //Move on to the next item (Check this, could cause the code to be blocked if there is at least one pending item in the queue. It may be better to futuretick instead
				$this->processFuture();
				return;
			}
            
            $this->limited = false; //Previous time has passed, so we can continue(Why is this here?! Get rid of it or we'll slow down the processing of every item in the queue)
            //$this->remaining = ($this->limit ? $this->limit : \INF);
        }
        
		
		if (\count($this->queue) >= 500) { //Buckets are not being processed correctly, so clear them out
            $this->queue = array(); 
        }
        
		echo '[PROCESSNEW->PROCESSITEM]' . PHP_EOL;
        $this->processItemNew($item); //Process the item
    }
	/**/
    
    /**
     * Processes a queue item.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest|\CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface|null  $item
     * @return void
     */
    protected function processItem($item) { //Skips over anything that isn't an APIRequest or a RatelimitBucketInterface
		echo '[PROCESSITEM]' . PHP_EOL;
        if ($item instanceof \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface) {
            if ($item->isBusy()) {
                $this->queue[] = $item; //Put it back in the queue because it's probably a busy interface
                
                foreach ($this->queue as $qitem) { //Process anything that isn't a busy RatelimitBucketInterface first
                    if (!($qitem instanceof \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface) || !$qitem->isBusy()) {
                        $this->processItemNew($qitem);
                        return;
                    }
                }
                
                return;
            }
            
            $item->setBusy(true);
            $buckItem = $this->extractFromBucket($item);
			if ($item instanceof \CharlotteDunois\Yasmin\HTTP\RatelimitBucket) {
				echo "BUCKET FOUND" . PHP_EOL;
               // return;
            }
            
            if (!($buckItem instanceof \React\Promise\ExtendedPromiseInterface)) {
                $buckItem = \React\Promise\resolve($buckItem);
            }
            
            $buckItem->done(function($req) use ($item) {
                $item->setBusy(false);
                
                if (!($req instanceof \CharlotteDunois\Yasmin\HTTP\APIRequest)) {
                    return;
                }
                
                $this->executeNew($req);
            }, array($this->client, 'handlePromiseRejection'));
        } else {
            if (!($item instanceof \CharlotteDunois\Yasmin\HTTP\APIRequest)) {
                return;
            }
            
            $this->executeNew($item);
        }
    }
	
	/**/
	//DEBUG TODO
	protected function processItemNew($item) { //Skips over anything that isn't an APIRequest or a RatelimitBucketInterface
		echo '[PROCESSITEM]' . PHP_EOL;
        if ($item instanceof \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface) {
            if ($item->isBusy()) {
                $this->queue[] = $item; //Put it back in the queue because it's probably a busy interface
                
                foreach ($this->queue as $qitem) { //Process anything that isn't a busy RatelimitBucketInterface first
                    if (!($qitem instanceof \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface) || !$qitem->isBusy()) {
                        $this->processItemNew($qitem);
                        return;
                    }
                }
                
                return;
            }
            
            $item->setBusy(true);
            $buckItem = $this->extractFromBucket($item);
			if ($item instanceof \CharlotteDunois\Yasmin\HTTP\RatelimitBucket) {
				//echo "BUCKET FOUND" . PHP_EOL;
               // return;
            }
            
            if (!($buckItem instanceof \React\Promise\ExtendedPromiseInterface)) {
                $buckItem = \React\Promise\resolve($buckItem);
				echo '[PROCESSITEMNEW RESOLVE]' . PHP_EOL;
            }
            echo '[PROCESSITEMNEW DONE]' . PHP_EOL;
            $buckItem->done(function($req) use ($item) {
                $item->setBusy(false);
                
                if (!($req instanceof \CharlotteDunois\Yasmin\HTTP\APIRequest)) {
                    return;
                }
                
                $this->executeNew($req);
            }, array($this->client, 'handlePromiseRejection'));
        } else {
            if (!($item instanceof \CharlotteDunois\Yasmin\HTTP\APIRequest)) {
                return;
            }
            
            $this->executeNew($item);
        }
    }
	/**/
    
    /**
     * Extracts an item from a ratelimit bucket.
     * @param \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface  $item
     * @return \CharlotteDunois\Yasmin\HTTP\APIRequest|bool|\React\Promise\ExtendedPromiseInterface
     */
    protected function extractFromBucket(\CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface $item) {
        if ($item->size() > 0) {
            $meta = $item->getMeta();
			//DEBUG TODO
            
            if ($meta instanceof \React\Promise\ExtendedPromiseInterface) {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $meta->then(function($data) use (&$item) {
                    if (!$data['limited']) {
                        $this->client->emit('debug', 'Retrieved item from bucket "'.$item->getEndpoint().' on bucket "'.$item->getBucketHeader().'"');
                        return $item->shift();
                    }
                    
                    $this->queue[] = $item;
                    
                    $this->client->addTimer(($data['resetTime'] - \microtime(true)), function() {
                        $this->processnew();
                    });
                }, function($error) use (&$item) {
                    $this->queue[] = $item;
                    $this->client->emit('error', $error);
                    
                    $this->processnew();
                    return false;
                });
            } else {
                if (!$meta['limited']) {
                    $this->client->emit('debug', 'Retrieved item from bucket "'.$item->getEndpoint().' on bucket "'.$item->getBucketHeader().'"');
                    return $item->shift();
                }
                
                $this->queue[] = $item;
                
                $this->client->addTimer(($meta['resetTime'] - \microtime(true)), function() {
                    $this->processnew();
                });
            }
        }
        
        return false;
    }
    
    /**
     * Executes an API Request.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest  $item
     * @return void
     */
    protected function execute(\CharlotteDunois\Yasmin\HTTP\APIRequest $item) {
        $endpoint = $this->getRatelimitEndpoint($item); echo '[EXECUTE ENDPOINT]:' . $endpoint . PHP_EOL;
		$ratelimit = null;
		
		//Always use the client's bucket to keep track
        

        echo '[EXECUTE]' . PHP_EOL;
		/**/
		//DEBUG TODO
		//check if a bucket is known for this endpoint
		if (array_key_exists($item->getEndpoint(), $this->client->xBuckets)){
			echo "[LIMITEDBUCKETHEADER]: ";
			foreach ($this->client->xBuckets[$item->getEndpoint()] as $limitedBucketHeader){
				echo "$limitedBucketHeader "; //use to handle ratelimit again based on the bucket limitations
			}
			echo PHP_EOL;
		}
		/**/
        if (!empty($endpoint)) {
            $ratelimit = $this->getRatelimitBucket($endpoint, $this->client->bucketHeader);
            $ratelimit->setBusy(true);
        }
		
		/* https://github.com/valzargaming/Yasmin/issues/7# */
		$bypassslow = $this->client->bypassSlow;
		if(!$bypassslow){ //Check for when the last call was made to this bucket and delay if it would happen too soon after the last	
			$slowmode = $this->client->slowMode;
			$lastcall = $this->client->lastCall ?? strtotime('January 1 2020');
			$skip = false;
			if (array_key_exists(($item->getBucketHeader() ?? $item->getEndpoint()), $this->client->lastBucketCall))
				$lastcall = $this->client->lastBucketCall[($item->getBucketHeader() ?? $item->getEndpoint())];
			else{
				$skip = true;
				$this->client->lastBucketCall[($item->getBucketHeader() ?? $item->getEndpoint())] = microtime(true);
			}
			$lastpassed = microtime(true) - $lastcall;
			
			$minpassed = 0.5;
			if($slowmode){
				$minpassed = 2;
				$slowpassed = ($this->client->lastSlow ?? strtotime('January 1 2020')) - microtime(true);
				if ($slowpassed > 300)
					$this->client->slowMode = false;
			}
			if ( ($lastpassed < $minpassed) || $skip ){
				$that = $this;
				$this->client->addTimer((0.50), function() use ($that, $item) {
					$that->executeNew($item);
				});
				return;
			}
		}
		/* https://github.com/valzargaming/Yasmin/issues/7# */
		
        $this->client->emit('debug', 'Executing item "'.$item->getEndpoint().' on bucket "'.$item->getBucketHeader().'"');
        
        $item->executeNew($ratelimit)->then(function($data) use ($item) {
            if ($data === 0) {
                $item->deferred->resolve();
            } elseif ($data !== -1) {
                $item->deferred->resolve($data);
            }
        }, function($error) use ($item) {
            $this->client->emit('debug', 'Request for item "'.$item->getEndpoint().'" failed with '.($error instanceof \Throwable ? 'exception '.\get_class($error) : 'error '.$error));
            $item->deferred->reject($error);
        })->then(null, function($error) {
            $this->client->handlePromiseRejection($error);
        })->done(function() use ($ratelimit, $endpoint) {
            if ($ratelimit instanceof \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface) {
                if (isset($this->bucketRatelimitPromises[($endpoint)])) {
                    $this->bucketRatelimitPromises[($endpoint)]->done(function() use ($ratelimit) {
                        $ratelimit->setBusy(false);
                        $this->processDelayed();
                    });
                } else {
                    $ratelimit->setBusy(false);
                    $this->processDelayed();
                }
            } else {
                $this->processDelayed();
            }
        });
		
		/* https://github.com/valzargaming/Yasmin/issues/7# */ 
		//DEBUG TODO
		$this->client->lastCall = microtime(true);
		/* https://github.com/valzargaming/Yasmin/issues/7# */
    }
	
	/**/
	//DEBUG TODO
	protected function executeNew(\CharlotteDunois\Yasmin\HTTP\APIRequest $item) {
        $endpoint = $this->getRatelimitEndpoint($item); echo '[EXECUTE ENDPOINT]:' . $endpoint . PHP_EOL;
		//$bucketHeader = $this->getRatelimitBucketHeader($item); echo '[EXECUTE BUCKETHEADER]:' . $bucketHeader . PHP_EOL;
		$ratelimit = null;
		
		$this->client->xBuckets = $this->client->getXBuckets(); //Always use the client's bucket to keep track
		//$bucketHeader = $this->client->xBuckets[$endpoint]; echo '[EXECUTE BUCKETHEADER]:' . $bucketHeader . PHP_EOL;
		$bucketHeader = null;
        

        echo '[EXECUTENEW]' . PHP_EOL;
		/**/
		//DEBUG TODO
		//check if a bucket is known for this endpoint
		if (array_key_exists($item->getEndpoint(), $this->client->xBuckets)){
			echo "[LIMITEDBUCKETHEADER]: ";
			foreach ($this->client->xBuckets[$item->getEndpoint()] as $limitedBucketHeader){
				echo "$limitedBucketHeader ";
				//handle ratelimit again based on the bucket limitations
			}
			echo PHP_EOL;
		}
		/**/
        if (!empty($endpoint)) {
            $ratelimit = $this->getRatelimitBucket($endpoint, $bucketHeader);
            $ratelimit->setBusy(true);
        }
		
		/* https://github.com/valzargaming/Yasmin/issues/7# */
		$bypassslow = $this->client->bypassSlow;
		if(!$bypassslow){ //Check for when the last call was made to this bucket and delay if it would happen too soon after the last	
			$slowmode = $this->client->slowMode;
			$lastcall = $this->client->lastCall ?? strtotime('January 1 2020');
			$skip = false;
			if (array_key_exists(($item->getBucketHeader() ?? $item->getEndpoint()), $this->client->lastBucketCall))
				$lastcall = $this->client->lastBucketCall[($item->getBucketHeader() ?? $item->getEndpoint())];
			else{
				$skip = true;
				$this->client->lastBucketCall[($item->getBucketHeader() ?? $item->getEndpoint())] = microtime(true);
			}
			$lastpassed = microtime(true) - $lastcall;
			
			$minpassed = 0.5;
			if($slowmode){
				$minpassed = 2;
				$slowpassed = ($this->client->lastSlow ?? strtotime('January 1 2020')) - microtime(true);
				if ($slowpassed > 300)
					$this->client->slowMode = false;
			}
			if ( ($lastpassed < $minpassed) || $skip ){
				$that = $this;
				$this->client->addTimer((0.50), function() use ($that, $item) {
					$that->executeNew($item);
				});
				return;
			}
		}
		/* https://github.com/valzargaming/Yasmin/issues/7# */
		
        $this->client->emit('debug', 'Executing item "'.$item->getEndpoint().' on bucket "'.$item->getBucketHeader().'"');
        
        $item->execute($ratelimit)->then(function($data) use ($item) {
            if ($data === 0) {
                $item->deferred->resolve();
            } elseif ($data !== -1) {
                $item->deferred->resolve($data);
            }
        }, function($error) use ($item) {
            $this->client->emit('debug', 'Request for item "'.$item->getEndpoint().'" failed with '.($error instanceof \Throwable ? 'exception '.\get_class($error) : 'error '.$error));
            $item->deferred->reject($error);
        })->then(null, function($error) {
            $this->client->handlePromiseRejection($error);
        })->done(function() use ($ratelimit, $bucketHeader, $endpoint) {
            if ($ratelimit instanceof \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface) {
                if (isset($this->bucketRatelimitPromises[($bucketHeader ?? $endpoint)])) {
                    $this->bucketRatelimitPromises[($bucketHeader ?? $endpoint)]->done(function() use ($ratelimit) {
                        $ratelimit->setBusy(false);
                        $this->processDelayed();
                    });
                } else {
                    $ratelimit->setBusy(false);
                    $this->processDelayed();
                }
            } else {
                $this->processDelayed();
            }
        });
		
		/* https://github.com/valzargaming/Yasmin/issues/7# */ 
		//DEBUG TODO
		$this->client->lastCall = microtime(true);
		/* https://github.com/valzargaming/Yasmin/issues/7# */
    }
	/**/
	
    
    /**
     * Turns an endpoint path to the ratelimit path.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest  $request
     * @return string
     */
    function getRatelimitEndpoint(\CharlotteDunois\Yasmin\HTTP\APIRequest $request) {
        $endpoint = $request->getEndpoint();
        
        if ($request->isReactionEndpoint()) {
            \preg_match('/channels\/(\d+)\/messages\/(\d+)\/reactions\/.*/', $endpoint, $matches);
            return 'channels/'.$matches[1].'/messages/'.$matches[2].'/reactions';
        }
        
        $firstPart = \substr($endpoint, 0, (\strpos($endpoint, '/') ?: \strlen($endpoint)));
        $majorRoutes = array('channels', 'guilds', 'webhooks');
        
        if (!\in_array($firstPart, $majorRoutes, true)) {
            return $firstPart;
        }
        
        \preg_match('/((?:.*?)\/(?:\d+))(?:\/messages\/((?:bulk(?:-|_)delete)|(?:\d+)){0,1})?/', $endpoint, $matches);
        
        if (\is_numeric(($matches[2] ?? null)) && $request->getMethod() === 'DELETE') {
            return 'delete@'.$matches[0];
        } elseif (\stripos(($matches[2] ?? ''), 'bulk') !== false) {
            return $matches[0];
        }
        
        return ($matches[1] ?? $endpoint);
    }
    
	 /**
     * Turns an bucket header to the ratelimit path.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest  $request
     * @return string
     */
	function getRatelimitBucketHeader(\CharlotteDunois\Yasmin\HTTP\APIRequest $request) {
		$bucketHeader = $request->getBucketHeader();
		return $bucketHeader;
	}
	
    /**
     * Gets the ratelimit bucket for the specific endpoint.
     * @param string $endpoint
     * @return \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface
     */
    protected function getRatelimitBucket(string $endpoint, ?string $bucketHeader) {
        $returnBucket = null;
		if ($bucketHeader)
		if (empty($this->ratelimits[$bucketHeader])) {
            $bucket = $this->bucketName;
            $this->ratelimits[$bucketHeader] = new $bucket($this, $endpoint, $bucketHeader);
			return $this->ratelimits[$bucketHeader];
        }
		if (empty($this->ratelimits[$endpoint])) {
            $bucket = $this->bucketName;
            $returnBucket = $this->ratelimits[$endpoint] = new $bucket($this, $endpoint, $bucketHeader);
			return $this->ratelimits[$endpoint];
        }
		return $this->ratelimits[$endpoint];
    }
    
    /**
     * Extracts ratelimits from a response.
     * @param \Psr\Http\Message\ResponseInterface  $response
     * @return mixed[]
     * @throws \Throwable
     */
    function extractRatelimit(\Psr\Http\Message\ResponseInterface $response) {
        $limit = ($response->hasHeader('X-RateLimit-Limit') ? ((int) $response->getHeader('X-RateLimit-Limit')[0]) : null);
        $remaining = ($response->hasHeader('X-RateLimit-Remaining') ? ((int) $response->getHeader('X-RateLimit-Remaining')[0]) : null);
        $resetTime = $this->extractRatelimitResetTime($response);
		
		$bucketHeader = ($response->hasHeader('X-RateLimit-Bucket') ? ((string) $response->getHeader('X-RateLimit-Bucket')[0]) : null);
        $this->bucketHeader = $bucketHeader ?? $this->bucketHeader;
		if ( (!(array_key_exists('resetTime', $this->client->xBuckets[$bucketHeader]))) || ($this->client->xBuckets[$bucketHeader]['resetTime'] < $resetTime) ) //Update the resetTimer for the bucket if a later resetTime is found
			$this->client->xBuckets[$bucketHeader]['resetTime'] = $resetTime;
		
        return \compact('limit', 'remaining', 'resetTime', 'bucketHeader');
    }
    
	/**/
	//DEBUG TODO
	function handleBucketHeaders(\Psr\Http\Message\ResponseInterface $response, ?\CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface $ratelimit = null){
		//Get the Bucket header
		if ($response->hasHeader('X-RateLimit-Bucket')){
			$bucketHeader = $this->client->bucketHeader = $this->bucketHeader = ($response->hasHeader('X-RateLimit-Bucket') ? ((string) $response->getHeader('X-RateLimit-Bucket')[0]) : null);
			echo "handleRatelimit buckerHeader: " . $bucketHeader . PHP_EOL;
			$lastEndpoint = $this->client->getLastEndpoint(); echo "handleRatelimit lastEndpoint: " . $lastEndpoint . PHP_EOL;
			
			//Associate the bucket header with the endpoint
			echo "[TEST] " . ($ratelimit->getEndpoint() ?? $lastEndpoint) . PHP_EOL;
			if (array_key_exists(($ratelimit->getEndpoint() ?? $lastEndpoint), $this->client->xBuckets)){
				echo '[EXISTS]' . PHP_EOL;
				echo '[RATELIMIT ENDPOINT]: ' . ($ratelimit->getEndpoint() ?? $lastEndpoint) . PHP_EOL;
				if (is_array($this->client->xBuckets[($ratelimit->getEndpoint() ?? $lastEndpoint)])){
					echo '[ISARRAY]' . PHP_EOL;
					if (!(in_array($bucketHeader, ($this->client->xBuckets[($ratelimit->getEndpoint() ?? $lastEndpoint)])))){
						echo '[NOT IN ARRAY]' . PHP_EOL;
						echo '[APIMANAGER XBUCKETS]' . $bucketHeader . PHP_EOL;
						$this->client->xBuckets[($ratelimit->getEndpoint() ?? $lastEndpoint)][] = $bucketHeader;
					}else{
						echo '[IN ARRAY]' . PHP_EOL;
					}
				}else{
					echo '[NEW ARRAY 2]' . PHP_EOL;
					$this->client->xBuckets[($ratelimit->getEndpoint() ?? $lastEndpoint)][] = $bucketHeader;
				}
			}else{//Endpoint key does not already exist in the array				
				echo '[NEW ARRAY 1]' . PHP_EOL;
				$this->client->xBuckets[($ratelimit->getEndpoint() ?? $lastEndpoint)][] = $bucketHeader;
			}
			//Associate the bucket's current ratelimit info to the stored bucket
			$xBucketLimit = $response->getHeader('X-RateLimit-Limit')[0];
			$xBucketRemaining = $response->getHeader('X-RateLimit-Remaining')[0];
			$xBucketReset = $response->getHeader('X-RateLimit-Reset')[0];
			
			$this->client->xBuckets[$bucketHeader]['limit'] = $xBucketLimit;
			$this->client->xBuckets[$bucketHeader]['remaining'] = $xBucketRemaining;
			$this->client->xBuckets[$bucketHeader]['reset'] = $xBucketReset;
		}
	}
	function handleRatelimitNew(\Psr\Http\Message\ResponseInterface $response, ?\CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface $ratelimit = null, bool $isReactionEndpoint = false) {
		$bucketHeader = $this->client->bucketHeader;
		
		
		$global = false;
        if ($response->hasHeader('X-RateLimit-Global')) {
            $global = true;
            
            $limit = $limit ?? $this->limit;
            $remaining = $this->client->xBuckets[$bucketHeader]['remaining'];
            $resetTime = $resetTime ?? $this->resetTime;
            
            if ($this->remaining === 0 && $this->resetTime > $ctime) {
                $this->limited = true;
                $this->client->emit('debug', 'Global ratelimit encountered, continuing in '.($this->resetTime - $ctime).' seconds');
            } else {
                $this->limited = false;
            }
        }
		/* DEBUG TODO (Rework of RatelimitBucket)
		elseif ($ratelimit !== null) {
            $set = $ratelimit->handleRatelimit($limit, $remaining, $resetTime); //FIX
            if ($set instanceof \React\Promise\ExtendedPromiseInterface) {
                $this->bucketRatelimitPromises[($ratelimit->getBucketHeader() ?? $ratelimit->getEndpoint())] = $set;
            }
        }
		*?
		$this->loop->futureTick(function() use ($ratelimit, $global, $limit, $remaining, $resetTime, $bucketHeader) {
            $this->client->emit('ratelimit', array(
                'endpoint' => ($ratelimit !== null ? $ratelimit->getEndpoint() : 'global'),
                'global' => $global,
                'limit' => $limit,
                'remaining' => $remaining,
                'resetTime' => $resetTime,
				'bucketHeader' => $bucketHeader
            ));
        });
		/**/
	}
	
	
	
    /**
     * Handles ratelimits.
     * @param \Psr\Http\Message\ResponseInterface                               $response
     * @param \CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface|null  $ratelimit
     * @param bool                                                              $isReactionEndpoint
     * @return void
     * @throws \Throwable
     */
    function handleRatelimit(\Psr\Http\Message\ResponseInterface $response, ?\CharlotteDunois\Yasmin\Interfaces\RatelimitBucketInterface $ratelimit = null, bool $isReactionEndpoint = false) {
        $ctime = \microtime(true);
        ['limit' => $limit, 'remaining' => $remaining, 'resetTime' => $resetTime, 'bucketHeader' => $bucketHeader] = $this->extractRatelimit($response);
        if ($isReactionEndpoint && !empty($resetTime)) {
            $resetTime = (float) \bcadd($ctime, '0.60', 3);
        }
		
        $global = false;
        if ($response->hasHeader('X-RateLimit-Global')) {
            $global = true;
            
            $this->limit = $limit ?? $this->limit;
            $this->remaining = $remaining ?? $this->remaining;
            $this->resetTime = $resetTime ?? $this->resetTime;
            
            if ($this->remaining === 0 && $this->resetTime > $ctime) {
                $this->limited = true;
                $this->client->emit('debug', 'Global ratelimit encountered, continuing in '.($this->resetTime - $ctime).' seconds');
            } else {
                $this->limited = false;
            }
        } elseif ($ratelimit !== null) {
            $set = $ratelimit->handleRatelimit($limit, $remaining, $resetTime); //FIX
            if ($set instanceof \React\Promise\ExtendedPromiseInterface) {
                $this->bucketRatelimitPromises[($ratelimit->getBucketHeader() ?? $ratelimit->getEndpoint())] = $set;
            }
        }
        $this->loop->futureTick(function() use ($ratelimit, $global, $limit, $remaining, $resetTime, $bucketHeader) {
            $this->client->emit('ratelimit', array(
                'endpoint' => ($ratelimit !== null ? $ratelimit->getEndpoint() : 'global'),
                'global' => $global,
                'limit' => $limit,
                'remaining' => $remaining,
                'resetTime' => $resetTime,
				'bucketHeader' => $bucketHeader
            ));
        });
    }
	
	function slowDown(){
		$this->client->slowMode = true;
		$this->client->lastSlow = microtime(true);
		$this->client->emit('debug', 'Slowdown mode enabled');
	}
	
	function getQueue(){
		return $this->queue;
	}
	
	function getXBuckets(){
		return $this->client->getXBuckets();
	}
	
	function getLastEndpoint(){
		return $this->client->getLastEndpoint();
	}
	
	function setLastEndpoint($endpoint){
		$this->client->setLastEndpoint($endpoint);
	}
    
    /**
     * Returns the ratelimit reset time.
     * @param \Psr\Http\Message\ResponseInterface  $response
     * @return float|null
     * @throws \Throwable
     */
    protected function extractRatelimitResetTime(\Psr\Http\Message\ResponseInterface $response): ?float {
        if ($response->hasHeader('Retry-After')) {
            $retry = (int) $response->getHeader('Retry-After')[0];
            $retryTime = \bcdiv($retry, 1000, 3);
            
            return ((float) \bcadd(\microtime(true), $retryTime, 3));
        } elseif ($response->hasHeader('X-RateLimit-Reset')) {
            $date = (new \DateTime(($response->getHeader('Date')[0] ?? 'now')))->getTimestamp();
            $reset = $response->getHeader('X-RateLimit-Reset')[0];
            
            $resetTime = \bcsub($reset, $date, 3);
            return ((float) \bcadd(\microtime(true), $resetTime, 3));
        }
        
        return null;
    }
}
