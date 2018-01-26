<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\HTTP;

/**
 * Represents a single HTTP request.
 * @internal
 */
class APIRequest {
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     */
    protected $api;
    
    /**
     * @var string
     */
    protected $url;
    
    /**
     * @var \React\Promise\Deferred
     */
    public $deferred;
    
    /**
     * @var string
     */
    private $method;
    
    /**
     * @var string
     */
    private $endpoint;
    
    /**
     * @var array
     */
    private $options = array();
    
    /**
     * Creates a new API Request.
     * DO NOT initialize this class yourself.
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager  $api
     * @param string                                   $method
     * @param string                                   $endpoint
     * @param array                                    $options
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api, string $method, string $endpoint, array $options) {
        $this->api = $api;
        $this->url = \CharlotteDunois\Yasmin\Constants::HTTP['url'].'v'.\CharlotteDunois\Yasmin\Constants::HTTP['version'].'/';
        
        $this->method = $method;
        $this->endpoint = \ltrim($endpoint, '/');
        $this->options = $options;
    }
    
    /**
     * Returns the request method.
     * @return string
     */
    function getMethod() {
        return $this->method;
    }
    
    
    /**
     * Returns the endpoint path.
     * @return string
     */
    function getEndpoint() {
        return $this->endpoint;
    }
    
    /**
     * Returns the Guzzle Request.
     * @return \GuzzleHttp\Psr7\Request
     */
    function request() {
        $url = $this->url.$this->endpoint;
        
        $options = array(
            'http_errors' => false,
            'protocols' => array('https'),
            'expect' => false,
            'headers' => array(
                'User-Agent' => 'DiscordBot (https://github.com/CharlotteDunois/Yasmin, '.\CharlotteDunois\Yasmin\Constants::VERSION.')'
            )
        );
        
        if(empty($this->options['noAuth']) && !empty($this->api->client->token)) {
            $options['headers']['Authorization'] = 'Bot '.$this->api->client->token;
        }
        
        if(!empty($this->options['files']) && \is_array($this->options['files'])) {
            $options['multipart'] = array();
            
            foreach($this->options['files'] as $file) {
                if(!isset($file['data']) && !isset($file['path'])) {
                    continue;
                }
                
                $field = ($file['field'] ?? 'file-'.\bin2hex(\random_bytes(3)));
                $options['multipart'][] = array(
                    'name' => $field,
                    'contents' => (isset($file['data']) ? $file['data'] : \fopen($file['path'], 'r')),
                    'filename' => (isset($file['name']) ? $file['name'] : (isset($file['path']) ? \basename($file['path']) : $field.'.jpg'))
                );
            }
            
            if(!empty($this->options['data'])) {
                $options['multipart'][] = array(
                    'name' => 'payload_json',
                    'contents' => \json_encode($this->options['data'])
                );
            }
        } elseif(!empty($this->options['data'])) {
            $options['json'] = $this->options['data'];
        }
        
        if(!empty($this->options['querystring'])) {
            $options['query'] = \http_build_query($this->options['querystring'], '', '&', \PHP_QUERY_RFC3986);
        }
        
        if(!empty($this->options['auditLogReason'])) {
            $options['headers']['X-Audit-Log-Reason'] = \rawurlencode(\trim($this->options['auditLogReason']));
        }
        
        $request = new \GuzzleHttp\Psr7\Request($this->method, $url);
        $request->requestOptions = $options;
        
        return $request;
    }
    
    function execute(\CharlotteDunois\Yasmin\HTTP\RatelimitBucket $ratelimit = null) {
        $request = $this->request();
        
        return \CharlotteDunois\Yasmin\Utils\URLHelpers::makeRequest($request, $request->requestOptions)->then(function ($response) {
            return $response;
        }, function ($error) {
            if($error->hasResponse()) {
                return $error->getResponse();
            }
            
            throw new \Exception($error->getMessage());
        })->then(function ($response) use ($ratelimit) {
            if(!$response) {
                return -1;
            }
            
            $status = $response->getStatusCode();
            $this->api->client->emit('debug', 'Got response for item "'.$this->endpoint.'" with HTTP status code '.$status);
            
            $this->api->handleRatelimit($response, $ratelimit);
            
            if($status === 204) {
                return 0;
            }
            
            $body = self::decodeBody($response);
            
            if($status >= 400) {
                $error = $this->handleAPIError($response, $body, $ratelimit);
                if($error === null) {
                    return -1;
                }
                
                throw $error;
            }
            
            return $body;
        });
    }
    
    /**
     * Gets the response body from the response.
     * @param \GuzzleHttp\Psr7\Response  $response
     * @return mixed
     */
    static function decodeBody(\GuzzleHttp\Psr7\Response $response) {
        $body = $response->getBody();
        if($body instanceof \GuzzleHttp\Psr7\Stream) {
            $body = $body->getContents();
        }
        
        $json = \json_decode($body, true);
        if($json === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception('Invalid API response: '.\json_last_error_msg());
        }
        
        return $json;
    }
    
    /**
     * Handles an API error.
     * @param \GuzzleHttp\Psr7\Response                          $response
     * @param mixed                                              $body
     * @param \CharlotteDunois\Yasmin\HTTP\RatelimitBucket|null  $ratelimit
     * @return \CharlotteDunois\Yasmin\HTTP\DiscordAPIException|\Exception|null
     */
    protected function handleAPIError(\GuzzleHttp\Psr7\Response $response, $body, \CharlotteDunois\Yasmin\HTTP\RatelimitBucket $ratelimit = null) {
        $status = $response->getStatusCode();
        
        if($status === 429 || $status >= 500) {
            $this->api->client->emit('debug', 'Unshifting item "'.$this->endpoint.'" due to HTTP '.$status);
            
            if($ratelimit !== null) {
                $this->api->unshiftQueue($ratelimit->unshift($this));
            } else {
                $this->api->unshiftQueue($this);
            }
            
            return null;
        }
        
        if($status >= 400 && $status < 500) {
            $error = new \CharlotteDunois\Yasmin\HTTP\DiscordAPIException($this->endpoint, $body);
        } else {
            $error = new \Exception($response->getReasonPhrase());
        }
        
        return $error;
    }
}
