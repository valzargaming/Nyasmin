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
 * Represents a single HTTP request.
 * @access private
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
     * @param \CharlotteDunois\Yasmin\HTTP\APIManager  $api
     * @param string                                   $method
     * @param string                                   $endpoint
     * @param array                                    $options
     */
    function __construct(\CharlotteDunois\Yasmin\HTTP\APIManager $api, string $method, string $endpoint, array $options) {
        $this->api = $api;
        $this->url = \CharlotteDunois\Yasmin\Constants::HTTP['url'].'v'.\CharlotteDunois\Yasmin\Constants::HTTP['version'].'/';
        
        $this->method = $method;
        $this->endpoint = $endpoint;
        $this->options = $options;
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
                'Authorization' => $this->api->getAuthorization(),
                'User-Agent' => 'CharlotteDunois/Yasmin (https://github.com/CharlotteDunois/Yasmin, '.\CharlotteDunois\Yasmin\Constants::VERSION.')'
            )
        );
        
        if(!empty($this->options['files']) && \is_array($this->options['files'])) {
            $options['multipart'] = array();
            foreach($this->options['files'] as $file) {
                if(!isset($file['data']) && !isset($file['path'])) {
                    continue;
                }
                
                $options['multipart'][] = array(
                    'name' => $file['field'] ?? 'file-'.\bin2hex(\random_bytes(3)),
                    'contents' => (isset($file['data']) ? $file['data'] : \fopen($file['path'], 'r')),
                    'filename' => (isset($file['name']) ? $file['name'] : (isset($file['path']) ? \basename($file['path']) : $file['name'].'jpg'))
                );
            }
            
            if(!empty($this->options['data'])) {
                $options['multipart'][] = array(
                    'name' => 'payload_json',
                    'contents' => json_encode($this->options['data'])
                );
            }
        } elseif(!empty($this->options['data'])) {
            $options['json'] = $this->options['data'];
        }
        
        if(!empty($this->options['querystring'])) {
            $options['query'] = \http_build_query($this->options['querystring'], '', '&', \PHP_QUERY_RFC3986);
        }
        
        if(!empty($this->options['auditLogReason']) && \is_string($this->options['auditLogReason'])) {
            $options['headers']['X-Audit-Log-Reason'] = \trim($this->options['auditLogReason']);
        }
        
        $request = new \GuzzleHttp\Psr7\Request($this->method, $url);
        $request->requestOptions = $options;
        
        return $request;
    }
}
