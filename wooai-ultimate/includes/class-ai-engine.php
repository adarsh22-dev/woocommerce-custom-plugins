<?php
if (!defined('ABSPATH')) exit;

class WooAI_AI_Engine {
    
    private $provider;
    private $api_key;
    
    public function __construct() {
        $this->provider = get_option('wooai_ai_provider', 'gemini');
        $this->api_key = get_option('wooai_' . $this->provider . '_key', '');
    }
    
    public function process($message, $session_id) {
        // Detect intent
        $intent = $this->detect_intent($message);
        
        // Handle specific intents
        if ($intent !== 'general') {
            return $this->handle_intent($intent, $message);
        }
        
        // Get AI response
        if (empty($this->api_key)) {
            return $this->fallback_response($message);
        }
        
        $ai_response = $this->call_ai($message);
        
        return array(
            'type' => 'text',
            'intent' => 'general',
            'message' => $ai_response
        );
    }
    
    private function detect_intent($message) {
        $message = strtolower($message);
        
        $intents = array(
            'bestselling' => array('bestselling', 'best selling', 'popular', 'top products'),
            'new_arrivals' => array('new arrivals', 'new products', 'latest', 'recent'),
            'offers' => array('offers', 'deals', 'discount', 'sale'),
            'recommended' => array('recommend', 'suggestion', 'what should i buy'),
            'callback' => array('call me', 'callback', 'contact me', 'phone'),
            'policies' => array('policy', 'return', 'shipping', 'privacy'),
            'orders' => array('order', 'track', 'delivery'),
            'account' => array('account', 'profile', 'login'),
        );
        
        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'general';
    }
    
    private function handle_intent($intent, $message) {
        switch ($intent) {
            case 'bestselling':
            case 'new_arrivals':
            case 'offers':
            case 'recommended':
                return array(
                    'type' => 'products',
                    'intent' => $intent,
                    'category' => $intent,
                    'message' => $this->get_category_message($intent)
                );
                
            case 'callback':
                return array(
                    'type' => 'callback',
                    'intent' => 'callback',
                    'message' => 'I\'d be happy to arrange a callback! Please fill in your details below:'
                );
                
            case 'policies':
                return array(
                    'type' => 'text',
                    'intent' => 'policies',
                    'message' => 'Which policy would you like to know about? We have Return Policy, Shipping Info, and Privacy Policy.'
                );
                
            case 'orders':
                if (is_user_logged_in()) {
                    return array(
                        'type' => 'text',
                        'intent' => 'orders',
                        'message' => 'You can view your orders in your account dashboard. Would you like me to take you there?'
                    );
                } else {
                    return array(
                        'type' => 'text',
                        'intent' => 'orders',
                        'message' => 'Please log in to view your order history.'
                    );
                }
                
            case 'account':
                return array(
                    'type' => 'text',
                    'intent' => 'account',
                    'message' => is_user_logged_in() 
                        ? 'You\'re logged in! How can I help with your account?' 
                        : 'Please log in to access your account.'
                );
        }
        
        return $this->fallback_response($message);
    }
    
    private function get_category_message($category) {
        $messages = array(
            'bestselling' => 'Here are our bestselling products:',
            'new_arrivals' => 'Check out our latest arrivals:',
            'offers' => 'Here are our current deals and offers:',
            'recommended' => 'Based on your interests, I recommend:'
        );
        
        return $messages[$category] ?? 'Here are some products for you:';
    }
    
    private function call_ai($message) {
        $context = "You are a helpful shopping assistant for " . get_bloginfo('name') . ". 
        Be friendly, concise (under 100 words), and helpful. 
        If asked about products, suggest using the quick action buttons.";
        
        switch ($this->provider) {
            case 'gemini':
                return $this->call_gemini($message, $context);
            case 'openai':
                return $this->call_openai($message, $context);
            case 'claude':
                return $this->call_claude($message, $context);
            default:
                return $this->fallback_response($message);
        }
    }
    
    private function call_gemini($message, $context) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->api_key;
        
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'contents' => array(array('parts' => array(array('text' => $context . "\n\n" . $message)))),
                'generationConfig' => array('temperature' => 0.7, 'maxOutputTokens' => 500)
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->fallback_response($message);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['candidates'][0]['content']['parts'][0]['text'] ?? $this->fallback_response($message);
    }
    
    private function call_openai($message, $context) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4',
                'messages' => array(
                    array('role' => 'system', 'content' => $context),
                    array('role' => 'user', 'content' => $message)
                ),
                'temperature' => 0.7,
                'max_tokens' => 500
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->fallback_response($message);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['message']['content'] ?? $this->fallback_response($message);
    }
    
    private function call_claude($message, $context) {
        $url = 'https://api.anthropic.com/v1/messages';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => 'claude-3-sonnet-20240229',
                'max_tokens' => 500,
                'messages' => array(array('role' => 'user', 'content' => $context . "\n\n" . $message))
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $this->fallback_response($message);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['content'][0]['text'] ?? $this->fallback_response($message);
    }
    
    private function fallback_response($message) {
        $responses = array(
            'default' => 'Thanks for your message! You can use the quick action buttons below to browse products, check policies, or request a callback.',
            'help' => 'I\'m here to help! Use the buttons below to browse bestsellers, new arrivals, or special offers.',
            'products' => 'Looking for products? Try the Bestselling, New Arrivals, or Offers buttons below!'
        );
        
        $message_lower = strtolower($message);
        
        if (strpos($message_lower, 'product') !== false || strpos($message_lower, 'buy') !== false) {
            return $responses['products'];
        }
        
        if (strpos($message_lower, 'help') !== false) {
            return $responses['help'];
        }
        
        return $responses['default'];
    }
}
