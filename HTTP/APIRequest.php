<?php
/**
 * Yasmin
 * Copyright 2017 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: MIT
*/

namespace CharlotteDunois\Yasmin\HTTP;

class APIRequest {
    protected $api;
    
    public $resolve;
    public $reject;
    
    private $method;
    private $endpoint;
    private $options = array();
    
    function __construct($api, $method, $endpoint, array $options) {
        $this->api = $api;
        
        $this->method = $method;
        $this->endpoint = $endpoint;
        $this->options = $options;
    }
    
    function getEndpoint() {
        return $this->endpoint;
    }
    
    function request() {
        $url = \CharlotteDunois\Yasmin\Constants::HTTP['url'].'/v'.\CharlotteDunois\Yasmin\Constants::HTTP['version'].'/'.$this->endpoint;
        
        $options = array(
            'http_errors' => false,
            'protocols' => array('https'),
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
