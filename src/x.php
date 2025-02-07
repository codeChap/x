<?php

declare(strict_types=1);

namespace codechap\x;

use codechap\x\Traits\getSet;

class x {

    use getSet;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $baseUrl = 'https://api.x.com/2';

    /**
     * @var string
     * 
     */
    public string $apiKey;
    public string $apiKeySecret;
    public string $accessToken;
    public string $accessTokenSecret;

    /**
     * @var array Supported media types for uploads
     */
    private const SUPPORTED_MEDIA_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    /**
     * @var int Maximum file size in bytes (5MB)
     */
    private const MAX_MEDIA_SIZE = 5242880;

    /**
     * @var array Additional constants for API configuration
     */
    private const API_ENDPOINTS = [
        'TWEETS' => '/tweets',
        'MEDIA_UPLOAD' => 'https://upload.twitter.com/1.1/media/upload.json'
    ];
    
    private const RATE_LIMIT_WINDOW = 900; // 15 minutes in seconds
    private const MAX_TWEETS_PER_WINDOW = 300;
    private const THREAD_DELAY_MS = 500000; // 500ms between thread tweets
    
    /**
     * @var array Cache for OAuth headers
     */
    private $oauthHeadersCache = [];
    
    /**
     * @var int Counter for rate limiting
     */
    private $tweetCount = 0;
    private $rateWindowStart;

    /**
     * Initializes the X/Twitter connection
     * @throws \Exception
     */
    public function init(): void
    {
        $this->validateCredentials();
        $this->rateWindowStart = time();
        $this->generateOAuthHeaders();
    }

    /**
     * Validates API credentials
     * @throws \Exception
     */
    private function validateCredentials(): void
    {
        $requiredCredentials = [
            'apiKey' => 'X API key',
            'apiKeySecret' => 'X API key secret',
            'accessToken' => 'X access token',
            'accessTokenSecret' => 'X access token secret'
        ];

        foreach ($requiredCredentials as $credential => $message) {
            if (empty($this->$credential)) {
                throw new \Exception("$message is not set.");
            }
        }
    }

    /**
     * Checks rate limiting before making requests
     * @throws \Exception
     */
    private function checkRateLimit(): void
    {
        $currentTime = time();
        if ($currentTime - $this->rateWindowStart >= self::RATE_LIMIT_WINDOW) {
            $this->tweetCount = 0;
            $this->rateWindowStart = $currentTime;
        }

        if ($this->tweetCount >= self::MAX_TWEETS_PER_WINDOW) {
            throw new \Exception('Rate limit exceeded. Please wait before sending more tweets.');
        }
        
        $this->tweetCount++;
    }

    /**
     * Creates base string for signature
     * @param string $timestamp OAuth timestamp
     * @param string $nonce OAuth nonce
     * @param string $url Optional specific URL for signature
     * @return string Base string
     */
    private function createSignatureBaseString($timestamp, $nonce, $url = '')
    {
        $params = [
            'oauth_consumer_key'     => $this->apiKey,
            'oauth_nonce'            => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => $timestamp,
            'oauth_token'            => $this->accessToken,
            'oauth_version'          => '1.0'
        ];
        
        // Sort parameters alphabetically
        ksort($params);
        
        $paramStr = http_build_query($params);
        $paramStr = str_replace(['+', '%7E'], ['%20', '~'], $paramStr);
        
        // Fix: Use the correct URL or default to tweets endpoint
        $targetUrl = $url ?: $this->baseUrl . self::API_ENDPOINTS['TWEETS'];
        
        $baseString = 'POST&' . rawurlencode($targetUrl) . '&' . rawurlencode($paramStr);
        
        return $baseString;
    }

    /**
     * Generates OAuth signature
     * @param string $timestamp OAuth timestamp
     * @param string $nonce OAuth nonce
     * @param string $url Optional specific URL for signature
     * @return string Signature
     */
    private function generateSignature($timestamp, $nonce, $url = '')
    {
        $signatureBaseStr = $this->createSignatureBaseString($timestamp, $nonce, $url);
        $signingKey = rawurlencode($this->apiKeySecret) . '&' . 
                     rawurlencode($this->accessTokenSecret);
        
        return base64_encode(hash_hmac('sha1', $signatureBaseStr, $signingKey, true));
    }

    /**
     * Generates and caches OAuth headers
     * @param string $url Optional specific URL for headers
     * @return array
     */
    private function generateOAuthHeaders(string $url = ''): array
    {
        $cacheKey = $url ?: 'default';
        
        if (isset($this->oauthHeadersCache[$cacheKey]) && 
            (time() - $this->oauthHeadersCache[$cacheKey]['timestamp'] < 300)) {
            return $this->oauthHeadersCache[$cacheKey]['headers'];
        }

        $oauthTimestamp = time();
        $oauthNonce = bin2hex(random_bytes(16));
        $signature = $this->generateSignature($oauthTimestamp, $oauthNonce, $url);

        $headers = [
            'Authorization: OAuth ' .
                'oauth_consumer_key="' . rawurlencode($this->apiKey) . '",' .
                'oauth_nonce="' . rawurlencode($oauthNonce) . '",' .
                'oauth_signature="' . rawurlencode($signature) . '",' .
                'oauth_signature_method="HMAC-SHA1",' .
                'oauth_timestamp="' . rawurlencode((string)$oauthTimestamp) . '",' .
                'oauth_token="' . rawurlencode($this->accessToken) . '",' .
                'oauth_version="1.0"',
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $this->oauthHeadersCache[$cacheKey] = [
            'headers' => $headers,
            'timestamp' => time()
        ];

        return $headers;
    }

    /**
     * Posts a tweet or thread to X/Twitter
     * @param array $message The message to tweet
     * @return array Response data
     * @throws \Exception If tweet posting fails
     */
    public function post($message)
    {
        if (empty($message)) {
            throw new \Exception('Invalid message format');
        }

        if(!is_array($message)) {
            $message = [$message];
        }

        // Determine the type of tweet
        $type = count($message) > 1 ? 'thread' : 'standard';

        switch ($type) {
            case 'standard':
                $params = ["text" => $message[0]->content];
                if (!empty($message[0]->image)) {
                    $pathToImage = $this->validateImage($message[0]->image);
                    $mediaId = $this->uploadMedia($pathToImage);
                    $params['media'] = ['media_ids' => [$mediaId]];
                }
                $result = $this->makeRequest(self::API_ENDPOINTS['TWEETS'], $params);
                break;
                
            case 'thread':
                $result = $this->postThread($message);
                break;
                
            default:
                throw new \Exception("Unknown tweet type: {$type}");
        }

        return $result;
    }

    /**
     * Validates image file before upload
     * @param string $imagePath Path to image file
     * @return string Validated full path to image
     * @throws \Exception If image is invalid
     */
    private function validateImage($imagePath)
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("Image not found: {$imagePath}");
        }

        if (filesize($imagePath) > self::MAX_MEDIA_SIZE) {
            throw new \Exception("Image size exceeds maximum allowed size of 5MB");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath);
        finfo_close($finfo);

        if (!in_array($mimeType, self::SUPPORTED_MEDIA_TYPES)) {
            throw new \Exception("Unsupported image type: {$mimeType}");
        }

        return $imagePath;
    }

    /**
     * Posts a thread of tweets
     * @param array $message The messages to post as thread
     * @return array The last tweet response
     * @throws \Exception If thread posting fails
     */
    private function postThread($message)
    {
        if (count($message) > 25) {
            throw new \Exception("Thread exceeds maximum allowed tweets (25)");
        }

        $previousTweetId = null;
        $lastResult = null;

        foreach ($message as $tweet) {
            if (empty($tweet->content)) {
                continue;
            }

            $params = ["text" => $tweet->content];
            if ($previousTweetId) {
                $params["reply"] = ["in_reply_to_tweet_id" => $previousTweetId];
            }

            // Handle images in thread tweets if present
            if (!empty($tweet->image)) {
                $pathToImage = $this->validateImage($tweet->image);
                $mediaId = $this->uploadMedia($pathToImage);
                $params['media'] = ['media_ids' => [$mediaId]];
            }

            $lastResult = $this->makeRequest(self::API_ENDPOINTS['TWEETS'], $params);
            $previousTweetId = $lastResult['data']['id'];

            // Add small delay between tweets to prevent rate limiting
            usleep(self::THREAD_DELAY_MS); // 500ms delay
        }

        return $lastResult;
    }

    /**
     * Makes an API request to X/Twitter with retry mechanism
     * @throws \Exception
     */
    private function makeRequest(string $endpoint, array $data = [], string $method = 'POST', int $retries = 3): array
    {
        $this->checkRateLimit();
        $lastException = null;

        for ($i = 0; $i < $retries; $i++) {
            try {
                $url = $this->baseUrl . $endpoint;
                $headers = $this->generateOAuthHeaders($url);
                
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_CUSTOMREQUEST  => $method,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_VERBOSE        => false,
                    CURLOPT_TIMEOUT        => 10
                ]);

                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if (curl_errno($ch)) {
                    throw new \Exception('Curl error: ' . curl_error($ch));
                }
                
                curl_close($ch);

                if ($httpCode < 200 || $httpCode >= 300) {
                    throw new \Exception("API request failed (HTTP {$httpCode}): " . $response);
                }

                $decodedResponse = json_decode($response, true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
                }

                return $decodedResponse;

            } catch (\Exception $e) {
                $lastException = $e;
                if ($i < $retries - 1) {
                    usleep(pow(2, $i) * 1000000); // Exponential backoff: 1s, 2s, 4s
                    continue;
                }
            }
        }

        throw new \Exception('Request failed after ' . $retries . ' retries. Last error: ' . $lastException->getMessage());
    }

    /**
     * Uploads media to X/Twitter
     * @param string $imagePath Path to image file
     * @return string Media ID
     * @throws \Exception If upload fails
     */
    private function uploadMedia($imagePath)
    {
        // Read image file
        $imageData = file_get_contents($imagePath);
        if ($imageData === false) {
            throw new \Exception("Failed to read image file: {$imagePath}");
        }

        // Get MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath);
        finfo_close($finfo);

        // For media upload, we need to use v1.1 API endpoint
        $mediaUploadUrl = self::API_ENDPOINTS['MEDIA_UPLOAD'];
        
        // Generate new OAuth parameters for media upload
        $oauthTimestamp = time();
        $oauthNonce = bin2hex(random_bytes(16));
        
        // Create signature base string for media upload
        $params = [
            'oauth_consumer_key'     => $this->apiKey,
            'oauth_nonce'            => $oauthNonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => $oauthTimestamp,
            'oauth_token'            => $this->accessToken,
            'oauth_version'          => '1.0'
        ];
        
        // Sort parameters alphabetically
        ksort($params);
        
        $paramStr = http_build_query($params);
        $paramStr = str_replace(['+', '%7E'], ['%20', '~'], $paramStr);
        
        $baseString = 'POST&' . rawurlencode($mediaUploadUrl) . '&' . rawurlencode($paramStr);
        $signingKey = rawurlencode($this->apiKeySecret) . '&' . 
                     rawurlencode($this->accessTokenSecret);
        
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
        
        // Create OAuth header for media upload
        $authHeader = 'Authorization: OAuth ' .
            'oauth_consumer_key="' . rawurlencode($this->apiKey) . '",' .
            'oauth_nonce="' . rawurlencode($oauthNonce) . '",' .
            'oauth_signature="' . rawurlencode($signature) . '",' .
            'oauth_signature_method="HMAC-SHA1",' .
            'oauth_timestamp="' . rawurlencode((string)$oauthTimestamp) . '",' .
            'oauth_token="' . rawurlencode($this->accessToken) . '",' .
            'oauth_version="1.0"';

        // Prepare upload parameters
        $boundary = uniqid();
        $postFields = '';
        $postFields .= "--{$boundary}\r\n";
        $postFields .= "Content-Disposition: form-data; name=\"media\"; filename=\"media\"\r\n";
        $postFields .= "Content-Type: {$mimeType}\r\n\r\n";
        $postFields .= $imageData . "\r\n";
        $postFields .= "--{$boundary}--\r\n";

        $ch = curl_init($mediaUploadUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                $authHeader,
                "Content-Type: multipart/form-data; boundary={$boundary}"
            ],
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_VERBOSE        => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Media upload failed (HTTP {$httpCode}): " . $response);
        }

        $result = json_decode($response, true);
        if (!isset($result['media_id_string'])) {
            throw new \Exception('Media upload failed: No media ID in response');
        }

        return $result['media_id_string'];
    }
} 