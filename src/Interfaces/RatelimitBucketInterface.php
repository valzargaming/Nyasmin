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
 * Manages a route ratelimit.
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
