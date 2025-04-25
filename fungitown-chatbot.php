<?php
/*
Plugin Name: Fungitown Chatbot
Description: Fixed 400 Error Version
Version: 1.5
*/

class Fungitown_Chatbot {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_chatbot']);
        add_action('wp_ajax_www_chatbot', [$this, 'handle_chat']);
        add_action('wp_ajax_nopriv_www_chatbot', [$this, 'handle_chat']);
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'fungitown-chatbot-style',
            plugins_url('style.css', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__) . 'style.css')
        );
        
        wp_enqueue_script(
            'fungitown-chatbot-script',
            plugins_url('chatbot.js', __FILE__),
            ['jquery'],
            filemtime(plugin_dir_path(__FILE__) . 'chatbot.js'),
            true
        );

        wp_localize_script(
            'fungitown-chatbot-script',
            'fungiChatData',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fungi_chat_nonce')
            ]
        );
    }

    public function render_chatbot() {
        echo '<div id="fungitown-chatbot">
                <div class="chat-header">🍄 Fungibot</div>
                <div class="chat-messages"></div>
                <div class="chat-input">
                    <input type="text" placeholder="Ask about mushrooms..." autocomplete="off">
                    <button>Send</button>
                </div>
              </div>';
    }

    public function handle_chat() {
        try {
            // Verify nonce from POST data
            if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'fungi_chat_nonce')) {
                throw new Exception('Security verification failed');
            }

            // Get messages from POST
            $messages = isset($_POST['messages']) ? json_decode(stripslashes($_POST['messages']), true) : [];
            
            if (empty($messages)) {
                throw new Exception('No messages received');
            }

            // Add system message
            array_unshift($messages, [
                'role' => 'system',
                'content' => 'You are Fungibot. First answer questions directly, then add a fungal analogy with 🍄 emojis.'
            ]);

            // Call OpenAI API
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . OPENAI_API_KEY
                ],
                'body' => json_encode([
                    'model' => 'gpt-4-turbo',
                    'messages' => $messages,
                    'temperature' => 0.7
                ]),
                'timeout' => 30
            ]);

            if (is_wp_error($response)) {
                throw new Exception('API request failed: ' . $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (empty($body['choices'][0]['message']['content'])) {
                throw new Exception('Empty response from API');
            }

            wp_send_json_success([
                'reply' => $body['choices'][0]['message']['content']
            ]);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}

new Fungitown_Chatbot();