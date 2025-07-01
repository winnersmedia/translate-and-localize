<?php
/**
 * Grok API integration class
 */

class TAL_Grok_API {
    
    private $api_key;
    private $model;
    private $api_endpoint = 'https://api.x.ai/v1/chat/completions';
    
    public function __construct() {
        $this->api_key = get_option('tal_grok_api_key');
        $this->model = get_option('tal_grok_model', 'grok-beta');
    }
    
    /**
     * Send translation request to Grok API
     */
    public function translate($prompt) {
        if (empty($this->api_key)) {
            throw new Exception('API key not configured');
        }
        
        $timeout = get_option('tal_request_timeout', 120);
        
        $request_data = array(
            'model' => $this->model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a professional translator and content localizer. Translate and adapt the content while preserving HTML formatting and maintaining cultural relevance for the target audience.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.3,
            'max_tokens' => 4000
        );
        
        $args = array(
            'timeout' => $timeout,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($request_data),
            'method' => 'POST',
            'data_format' => 'body'
        );
        
        // Make API request
        $response = wp_remote_post($this->api_endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : 'API request failed with status ' . $response_code;
            
            throw new Exception($error_message);
        }
        
        $data = json_decode($response_body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            $response = $this->translate('Test: Translate "Hello World" to Spanish.');
            return !empty($response);
        } catch (Exception $e) {
            return false;
        }
    }
}