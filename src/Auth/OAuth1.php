<?php

namespace codechap\x\Auth;

use codechap\x\Traits\OAuthTrait;

class OAuth1 {
    use OAuthTrait;

    private string $apiKey;
    private string $apiKeySecret;
    private string $accessToken;
    private string $accessTokenSecret;
    private array $oauthHeadersCache = [];

    public function __construct(string $apiKey, string $apiKeySecret, string $accessToken, string $accessTokenSecret) 
    {
        $this->apiKey = trim($apiKey);
        $this->apiKeySecret = trim($apiKeySecret);
        $this->accessToken = trim($accessToken);
        $this->accessTokenSecret = trim($accessTokenSecret);
    }

    /**
     * Generates and caches OAuth headers
     */
    public function generateHeaders(string $url = '', string $method = 'POST'): array
    {
        $cacheKey = $method . ':' . ($url ?: 'default');
        
        if (isset($this->oauthHeadersCache[$cacheKey]) && 
            (time() - $this->oauthHeadersCache[$cacheKey]['timestamp'] < 300)) {
            return $this->oauthHeadersCache[$cacheKey]['headers'];
        }

        $params = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0'
        ];

        $baseString = $this->createSignatureBaseString($params, $url, $method);
        $signingKey = rawurlencode($this->apiKeySecret) . '&' . rawurlencode($this->accessTokenSecret);
        
        $params['oauth_signature'] = $this->generateSignature($baseString, $signingKey);
        
        $headers = [
            'Authorization: OAuth ' .
                'oauth_consumer_key="' . rawurlencode($this->apiKey) . '",' .
                'oauth_nonce="' . rawurlencode($params['oauth_nonce']) . '",' .
                'oauth_signature="' . rawurlencode($params['oauth_signature']) . '",' .
                'oauth_signature_method="' . rawurlencode($params['oauth_signature_method']) . '",' .
                'oauth_timestamp="' . rawurlencode((string)$params['oauth_timestamp']) . '",' .
                'oauth_token="' . rawurlencode($this->accessToken) . '",' .
                'oauth_version="' . rawurlencode($params['oauth_version']) . '"',
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $this->oauthHeadersCache[$cacheKey] = [
            'headers' => $headers,
            'timestamp' => time()
        ];

        return $headers;
    }
} 