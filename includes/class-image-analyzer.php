<?php
namespace SmartAltText;

class ImageAnalyzer {
    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function analyze_image($image_url) {
        $debug_info = [];
        try {
            $debug_info[] = '[ImageAnalyzer-1] Starting image analysis with OpenAI';
            $debug_info[] = '[ImageAnalyzer-2] Image URL: ' . $image_url;
            $debug_info[] = '[ImageAnalyzer-3] API Key length: ' . strlen($this->api_key);

            // Validate image URL
            if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
                $debug_info[] = '[ImageAnalyzer-ERROR] Invalid image URL format';
                return [
                    'success' => false,
                    'error' => 'Invalid image URL format',
                    'debug_info' => $debug_info
                ];
            }

            // Check if image exists and is accessible
            $image_headers = @get_headers($image_url, 1);
            if ($image_headers === false) {
                $debug_info[] = '[ImageAnalyzer-ERROR] Failed to fetch image headers';
                return [
                    'success' => false,
                    'error' => 'Failed to fetch image headers - URL may be inaccessible',
                    'debug_info' => $debug_info
                ];
            }
            $debug_info[] = '[ImageAnalyzer-4] Image headers received';
            
            if (!$image_headers || strpos($image_headers[0], '200') === false) {
                $debug_info[] = '[ImageAnalyzer-ERROR] Image not accessible: ' . $image_headers[0];
                return [
                    'success' => false,
                    'error' => 'Image not accessible: ' . $image_headers[0],
                    'debug_info' => $debug_info
                ];
            }

            // Verify content type is an image
            $content_type = is_array($image_headers['Content-Type']) 
                ? $image_headers['Content-Type'][0] 
                : $image_headers['Content-Type'];
                
            if (!isset($content_type) || strpos($content_type, 'image/') !== 0) {
                $debug_info[] = '[ImageAnalyzer-ERROR] Invalid content type: ' . ($content_type ?? 'unknown');
                return [
                    'success' => false,
                    'error' => 'Invalid content type: ' . ($content_type ?? 'unknown'),
                    'debug_info' => $debug_info
                ];
            }
            $debug_info[] = '[ImageAnalyzer-5] Content type verified: ' . $content_type;

            // Prepare the request body for OpenAI API
            $request_body = [
                'model' => 'gpt-4-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Generate a concise, descriptive alt text for this image. Focus on the main subject and important details. Keep it under 125 characters.'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $image_url
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 150,
                'temperature' => 0.1
            ];

            $debug_info[] = '[ImageAnalyzer-6] Request body prepared';

            // Prepare the request
            $request_args = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body' => wp_json_encode($request_body),
                'timeout' => 60,
                'sslverify' => true,
                'blocking' => true,
                'httpversion' => '1.1'
            ];

            $debug_info[] = '[ImageAnalyzer-7] Request headers prepared';
            $debug_info[] = '[ImageAnalyzer-8] Making request to OpenAI API';

            // Add error handling for JSON encoding
            if (json_last_error() !== JSON_ERROR_NONE) {
                $debug_info[] = '[ImageAnalyzer-ERROR] JSON encode error: ' . json_last_error_msg();
                return [
                    'success' => false,
                    'error' => 'Failed to encode request body: ' . json_last_error_msg(),
                    'debug_info' => $debug_info
                ];
            }

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $request_args);
            $debug_info[] = '[ImageAnalyzer-9] Response received';

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $debug_info[] = '[ImageAnalyzer-ERROR] WP Error: ' . $error_message;
                return [
                    'success' => false,
                    'error' => 'WordPress error: ' . $error_message,
                    'debug_info' => $debug_info
                ];
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            $debug_info[] = '[ImageAnalyzer-10] Response code: ' . $response_code;

            if ($response_code !== 200) {
                $debug_info[] = '[ImageAnalyzer-ERROR] Non-200 response code: ' . $response_code;
                $debug_info[] = '[ImageAnalyzer-ERROR] Response body: ' . $response_body;
                return [
                    'success' => false,
                    'error' => 'API Error (' . $response_code . '): ' . $response_body,
                    'debug_info' => $debug_info
                ];
            }

            $data = json_decode($response_body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $debug_info[] = '[ImageAnalyzer-ERROR] JSON decode error: ' . json_last_error_msg();
                return [
                    'success' => false,
                    'error' => 'Failed to decode API response: ' . json_last_error_msg(),
                    'debug_info' => $debug_info
                ];
            }

            $debug_info[] = '[ImageAnalyzer-11] Response decoded successfully';

            if (!isset($data['choices'][0]['message']['content'])) {
                $debug_info[] = '[ImageAnalyzer-ERROR] Invalid API response structure';
                return [
                    'success' => false,
                    'error' => 'Invalid API response structure',
                    'debug_info' => $debug_info
                ];
            }

            $alt_text = trim($data['choices'][0]['message']['content']);
            if (empty($alt_text)) {
                $debug_info[] = '[ImageAnalyzer-ERROR] Empty alt text generated';
                return [
                    'success' => false,
                    'error' => 'Empty alt text generated',
                    'debug_info' => $debug_info
                ];
            }

            // Ensure alt text is under 125 characters
            if (strlen($alt_text) > 125) {
                $alt_text = substr($alt_text, 0, 122) . '...';
            }

            $debug_info[] = '[ImageAnalyzer-SUCCESS] Generated alt text: ' . $alt_text;
            
            return [
                'success' => true,
                'alt_text' => $alt_text,
                'debug_info' => $debug_info
            ];

        } catch (\Exception $e) {
            $debug_info[] = '[ImageAnalyzer-ERROR] Exception: ' . $e->getMessage();
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debug_info' => $debug_info
            ];
        }
    }
} 