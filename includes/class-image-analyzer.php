<?php
namespace SmartAltText;

class ImageAnalyzer {
    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function analyze_image($image_url) {
        try {
            error_log('Smart Alt Text - ImageAnalyzer: [1] Starting image analysis with X.AI');

            // Prepare the request
            $response = wp_remote_post('https://api.x.ai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => json_encode([
                    'model' => 'grok-2-vision-1212',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => $image_url,
                                        'detail' => 'high'
                                    ]
                                ],
                                [
                                    'type' => 'text',
                                    'text' => 'Generate a concise, descriptive alt text for this image. Focus on the main subject and important details. Keep it under 125 characters.'
                                ]
                            ]
                        ]
                    ],
                    'temperature' => 0.01
                ]),
                'timeout' => 30
            ]);

            error_log('Smart Alt Text - ImageAnalyzer: [2] Received response from X.AI');

            if (is_wp_error($response)) {
                error_log('Smart Alt Text - ImageAnalyzer: [ERROR] WP Error - ' . $response->get_error_message());
                throw new \Exception($response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                $error_body = wp_remote_retrieve_body($response);
                error_log('Smart Alt Text - ImageAnalyzer: [ERROR] API Error - ' . $error_body);
                throw new \Exception('API returned error: ' . $response_code . ' - ' . $error_body);
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Smart Alt Text - ImageAnalyzer: [ERROR] JSON decode error - ' . json_last_error_msg());
                throw new \Exception('Failed to decode API response');
            }

            error_log('Smart Alt Text - ImageAnalyzer: [3] Successfully decoded response');

            // Extract and validate alt text from the response
            $alt_text = trim($data['choices'][0]['message']['content'] ?? '');
            if (empty($alt_text)) {
                error_log('Smart Alt Text - ImageAnalyzer: [ERROR] Empty alt text generated');
                throw new \Exception('Empty alt text generated');
            }

            // Ensure alt text is under 125 characters
            if (strlen($alt_text) > 125) {
                $alt_text = substr($alt_text, 0, 122) . '...';
            }

            error_log('Smart Alt Text - ImageAnalyzer: [4] Generated alt text - ' . $alt_text);
            return $alt_text;

        } catch (\Exception $e) {
            error_log('Smart Alt Text - ImageAnalyzer: [ERROR] Exception - ' . $e->getMessage());
            error_log('Smart Alt Text - ImageAnalyzer: [ERROR] Stack trace - ' . $e->getTraceAsString());
            throw $e;
        }
    }
} 