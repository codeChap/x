<?php

namespace codechap\x\Auth;

use codechap\x\Traits\OAuthTrait;

class XAuth
{
    use OAuthTrait;

    private string $apiKey;
    private string $apiKeySecret;
    private string $callbackUrl;
    private ?string $oauthToken = null;
    private ?string $oauthTokenSecret = null;

    /**
     * Constructor for the XAuth class.
     *
     * @param string $apiKey The API key.
     * @param string $apiKeySecret The API key secret.
     * @param string $callbackUrl The callback URL.
     */
    public function __construct(
        string $apiKey,
        string $apiKeySecret,
        string $callbackUrl
    ) {
        $this->apiKey = trim($apiKey);
        $this->apiKeySecret = trim($apiKeySecret);
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * Get the authorization URL for the user to authorize the application.
     *
     * @return string The authorization URL.
     */
    public function getAuthUrl(): string
    {
        $params = [
            "oauth_callback" => $this->callbackUrl,
            "oauth_consumer_key" => $this->apiKey,
            "oauth_nonce" => time(),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_timestamp" => time(),
            "oauth_version" => "1.0",
        ];

        $url = "https://api.twitter.com/oauth/request_token";
        $baseString = $this->createSignatureBaseString($params, $url, "POST");
        $signingKey = rawurlencode($this->apiKeySecret) . "&";

        $params["oauth_signature"] = $this->generateSignature(
            $baseString,
            $signingKey
        );

        // Step 1: Get OAuth request token
        $requestTokenUrl = "https://api.twitter.com/oauth/request_token";

        // Make request
        $header = $this->buildAuthorizationHeader($params);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $requestTokenUrl,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: " . $header,
                "Content-Length: 0",
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($curl);

        if ($response === false) {
            throw new \Exception("Curl error: " . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 200) {
            throw new \Exception(
                "Twitter API error. HTTP code: " .
                    $httpCode .
                    ". Response: " .
                    $response
            );
        }

        $requestToken = [];
        parse_str($response, $requestToken);

        if (
            !isset($requestToken["oauth_token"]) ||
            !isset($requestToken["oauth_token_secret"])
        ) {
            throw new \Exception(
                "Invalid response from Twitter. Response: " . $response
            );
        }

        // Store tokens for later use
        $this->oauthToken = $requestToken["oauth_token"];
        $this->oauthTokenSecret = $requestToken["oauth_token_secret"];

        // Return authorization URL
        return "https://api.twitter.com/oauth/authorize?oauth_token={$this->oauthToken}";
    }

    /**
     * Handle callback from Twitter
     *
     * @param string $oauthToken
     * @param string $oauthVerifier
     * @return array
     */
    public function handleCallback($oauthToken, $oauthVerifier) : array
    {
        if ($oauthToken !== $this->oauthToken) {
            //throw new \Exception('Invalid OAuth token');
        }

        // Step 2: Get access token
        $accessTokenUrl = "https://api.twitter.com/oauth/access_token";

        $oauth = [
            "oauth_consumer_key" => $this->apiKey,
            "oauth_token" => $oauthToken,
            "oauth_verifier" => $oauthVerifier,
            "oauth_nonce" => time(),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_timestamp" => time(),
            "oauth_version" => "1.0",
        ];

        $baseString = $this->buildBaseString($accessTokenUrl, "POST", $oauth);
        $signingKey = $this->buildSigningKey(
            $this->apiKeySecret,
            $this->oauthTokenSecret
        );
        $oauth["oauth_signature"] = base64_encode(
            hash_hmac("sha1", $baseString, $signingKey, true)
        );

        $header = $this->buildAuthorizationHeader($oauth);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $accessTokenUrl,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: " . $header,
                "Content-Length: 0",
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $accessToken = [];
        parse_str($response, $accessToken);

        if (
            !isset($accessToken["oauth_token"]) ||
            !isset($accessToken["oauth_token_secret"])
        ) {
            throw new \Exception(
                "Invalid response from Twitter. Response: " . $response
            );
        }

        return [
            "access_token" => $accessToken["oauth_token"],
            "access_token_secret" => $accessToken["oauth_token_secret"],
            "user_id" => $accessToken["user_id"],
            "screen_name" => $accessToken["screen_name"],
        ];
    }

    /**
     * Build base string for OAuth signature
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @return string
     */
    private function buildBaseString($url, $method, $params) : string
    {
        $r = [];
        foreach ($params as $key => $value) {
            $r[] = rawurlencode($key) . "=" . rawurlencode($value);
        }
        return $method .
            "&" .
            rawurlencode($url) .
            "&" .
            rawurlencode(implode("&", $r));
    }

    /**
     * Build signing key for OAuth signature
     *
     * @param string $consumerSecret
     * @param string $tokenSecret
     * @return string
     */
    private function buildSigningKey($consumerSecret, $tokenSecret) : string
    {
        return rawurlencode($consumerSecret) . "&" . rawurlencode($tokenSecret);
    }

    /**
     * Build authorization header for OAuth signature
     *
     * @param array $oauth
     * @return string
     */
    private function buildAuthorizationHeader($oauth) : string
    {
        $r = [];
        foreach ($oauth as $key => $value) {
            $r[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }
        return "OAuth " . implode(", ", $r);
    }
}
