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
 * Represents a single HTTP request.
 * @access private
 */
class APIRequest {
    /**
     * @var \CharlotteDunois\Yasmin\HTTP\APIManager
     */
    protected $api;
    
    /**
     * @var \React\Promise\Promise
     */
    public $resolve;
    
    /**
     * @var \React\Promise\Promise
     */
    public $reject;
    
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
        $url = \CharlotteDunois\Yasmin\Constants::HTTP['url'].'v'.\CharlotteDunois\Yasmin\Constants::HTTP['version'].'/'.$this->endpoint;
        
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
                $options['multipart'][] = array(
                    'name' => $file['name'],
                    'contents' => (isset($file['data']) ? $file['data'] : \fopen($file['path'])),
                    'filename' => (isset($file['filename']) ? $file['filename'] : null)
                );
            }
            
            if(!empty($this->options['data'])) {
                foreach($this->options['data'] as $name => $data) {
                    $options['multipart'][] = array(
                        'name' => $name,
                        'contents' => $data
                    );
                }
            }
        } elseif(!empty($this->options['data'])) {
            $options['json'] = $this->options['data'];
        }
        
        if(!empty($this->options['querystring'])) {
            $options['query'] = $this->options['querystring'];
        }
        
        if(!empty($this->options['auditLogReason']) && \is_string($this->options['auditLogReason'])) {
            $options['headers']['X-Audit-Log-Reason'] = \trim($this->options['auditLogReason']);
        }
        
        return (new \GuzzleHttp\Psr7\Request($this->method, $url, $options));
    }
}
