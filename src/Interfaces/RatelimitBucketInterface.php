<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Interfaces;

/**
 * This interface defines required methods and their arguments for managing route ratelimits using various systems.<br>
 * The ratelimit bucket queue is always managed in memory (as in belongs to that process), however the ratelimits are distributed to the used system.
 *
 * Included are two ratelimit bucket systems:<br>
 *  * In memory ratelimit bucket using arrays - Class: <code>\CharlotteDunois\Yasmin\HTTP\RatelimitBucket</code> (default)<br>
 *  * Redis ratelimit bucket, using Athena to interface with Redis - Class: <code>\CharlotteDunois\Yasmin\HTTP\AthenaRatelimitBucket</code>
 *
 * To use a different one then the default, you have to pass the full qualified class name to the client constructor as client option <code>http.ratelimitbucket.name</code>.<br>
 * The Redis ratelimit bucket system uses Athena, an asynchronous redis cache for PHP. The package is called <code>charlottedunois/athena</code> (which is suggested on composer).<br>
 * To be able to use the Redis ratelimit bucket, you need to pass an instance of <code>AthenaCache</code> as client option <code>http.ratelimitbucket.athena</code> to the client.
 */
interface RatelimitBucketInterface {
    /**
     * Initializes the bucket.
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager  $api
     * @param string                                   $endpoint
     * @throws \RuntimeException
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api, string $endpoint);
    
    /**
     * Destroys the bucket.
     */
    function __destruct();
    
    /**
     * Sets the ratelimits from the response.
     * @param int|null  $limit
     * @param int|null  $remaining
     * @param int|null  $resetTime
     * @return \React\Promise\ExtendedPromiseInterface|void
     */
    function handleRatelimit(?int $limit, ?int $remaining, ?int $resetTime);
    
    /**
     * Returns the endpoint this bucket is for.
     * @return string
     */
    function getEndpoint(): string;
    
    /**
     * Returns the size of the queue.
     * @return int
     */
    function size(): int;
    
    /**
     * Pushes a new request into the queue.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $request
     * @return $this
     */
    function push(\CharlotteDunois\Yasmin\HTTP\APIRequest $request);
    
    /**
     * Unshifts a new request into the queue. Modifies remaining ratelimit.
     * @param \CharlotteDunois\Yasmin\HTTP\APIRequest $request
     * @return $this
     */
    function unshift(\CharlotteDunois\Yasmin\HTTP\APIRequest $request);
    
    /**
     * Retrieves ratelimit meta data.
     *
     * The resolved value must be:
     * <pre>
     * array(
     *     'limited' => bool,
     *     'resetTime' => int|null
     * )
     * </pre>
     *
     * @return \React\Promise\ExtendedPromiseInterface|array
     */
    function getMeta();
    
    /**
     * Returns the first queue item or false. Modifies remaining ratelimit.
     * @return \CharlotteDunois\Yasmin\HTTP\APIRequest|false
     */
    function shift();
    
    /**
     * Unsets all queue items.
     */
    function clear();
}
