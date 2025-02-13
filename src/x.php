<?php

declare(strict_types=1);

namespace codechap\x;

use codechap\x\Traits\getSet;
use codechap\x\Requests\post;
use codechap\x\Requests\me;

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
     * @var array Additional constants for API configuration
     */
    private const API_ENDPOINTS = [
        'TWEETS' => '/tweets',
        'MEDIA_UPLOAD' => 'https://upload.twitter.com/1.1/media/upload.json'
    ];
    
    private const RATE_LIMIT_WINDOW = 900; // 15 minutes in seconds
    private const MAX_TWEETS_PER_WINDOW = 300;
    
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
     * @param string $method HTTP method (GET/POST)
     * @return string Base string
     */
    private function createSignatureBaseString($timestamp, $nonce, $url = '', $method = 'POST')
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
        
        // Use the correct HTTP method in the base string
        $baseString = $method . '&' . rawurlencode($targetUrl) . '&' . rawurlencode($paramStr);
        
        return $baseString;
    }

    /**
     * Generates OAuth signature
     * @param string $timestamp OAuth timestamp
     * @param string $nonce OAuth nonce
     * @param string $url Optional specific URL for signature
     * @param string $method HTTP method (GET/POST)
     * @return string Signature
     */
    private function generateSignature($timestamp, $nonce, $url = '', $method = 'POST')
    {
        $signatureBaseStr = $this->createSignatureBaseString($timestamp, $nonce, $url, $method);
        $signingKey = rawurlencode($this->apiKeySecret) . '&' . 
                     rawurlencode($this->accessTokenSecret);
        
        return base64_encode(hash_hmac('sha1', $signatureBaseStr, $signingKey, true));
    }

    /**
     * Generates and caches OAuth headers
     * @param string $url Optional specific URL for headers
     * @param string $method HTTP method (GET/POST)
     * @return array
     */
    private function generateOAuthHeaders(string $url = '', string $method = 'POST'): array
    {
        $cacheKey = $method . ':' . ($url ?: 'default');
        
        if (isset($this->oauthHeadersCache[$cacheKey]) && 
            (time() - $this->oauthHeadersCache[$cacheKey]['timestamp'] < 300)) {
            return $this->oauthHeadersCache[$cacheKey]['headers'];
        }

        $oauthTimestamp = time();
        $oauthNonce = bin2hex(random_bytes(16));
        $signature = $this->generateSignature($oauthTimestamp, $oauthNonce, $url, $method);

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
        $poster = new post($this);
        return $poster->send($message);
    }

    /**
     * Makes an API request to X/Twitter with retry mechanism
     * @throws \Exception
     */
    public function makeRequest(string $endpoint, array $data = [], string $method = 'POST', int $retries = 3, bool $isMediaUpload = false): array
    {
        $this->checkRateLimit();
        $lastException = null;

        for ($i = 0; $i < $retries; $i++) {
            try {
                $url = $isMediaUpload ? self::API_ENDPOINTS['MEDIA_UPLOAD'] : $this->baseUrl . $endpoint;
                $headers = $this->generateOAuthHeaders($url, $method);
                
                $ch = curl_init($url);
                $curlOpts = [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_CUSTOMREQUEST  => $method,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_VERBOSE        => false,
                    CURLOPT_TIMEOUT        => 10
                ];

                if ($data && $method !== 'GET') {
                    if (isset($data['multipart'])) {
                        // For media uploads
                        $curlOpts[CURLOPT_POST] = true;
                        $curlOpts[CURLOPT_POSTFIELDS] = $data['multipart']['data'];
                        // Remove Content-Type header for multipart uploads
                        $curlOpts[CURLOPT_HTTPHEADER] = array_filter($headers, function($header) {
                            return !str_starts_with($header, 'Content-Type:');
                        });
                    } else {
                        $curlOpts[CURLOPT_POSTFIELDS] = json_encode($data);
                    }
                }

                curl_setopt_array($ch, $curlOpts);

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
     * Gets information about the authenticated user
     * @return array Response data
     * @throws \Exception If request fails
     */
    public function me(): array
    {
        $meRequest = new me($this);
        return $meRequest->get();
    }
} 