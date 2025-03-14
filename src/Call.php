<?php

declare(strict_types=1);

namespace codechap\x;

use codechap\x\Auth\OAuth1;
use codechap\x\Traits\GetSet;

class Call
{
    use GetSet;

    private string $baseUrl = "https://api.x.com/2";

    private OAuth1 $oauth;

    public string $apiKey;
    public string $apiKeySecret;
    public string $accessToken;
    public string $accessTokenSecret;
    public int $rateWindowStart;

    /**
     * Initialize the Call object with API credentials and OAuth1 instance.
     */
    public function init(): void
    {
        $this->validateCredentials();
        $this->rateWindowStart = time();
        $this->oauth = new OAuth1(
            $this->apiKey,
            $this->apiKeySecret,
            $this->accessToken,
            $this->accessTokenSecret
        );
    }

    /**
     * Validate API credentials.
     */
    private function validateCredentials(): void
    {
        $requiredCredentials = [
            "apiKey" => "X API key",
            "apiKeySecret" => "X API key secret",
            "accessToken" => "X access token",
            "accessTokenSecret" => "X access token secret",
        ];

        foreach ($requiredCredentials as $credential => $message) {
            if (empty($this->$credential)) {
                throw new \Exception("$message is not set.");
            }
        }
    }

    /**
     * Make a request to the Twitter API.
     * @param string $endpoint The endpoint to request.
     * @param array $data The data to send.
     * @param string $method The HTTP method to use.
     * @param bool $isMediaUpload Whether the request is a media upload.
     * @return array The response data.
     */
    public function makeRequest(
        string $endpoint,
        array $data = [],
        string $method = "POST",
        bool $isMediaUpload = false
    ): array {
        $lastException = null;

        $url = $isMediaUpload
            ? "https://upload.twitter.com/1.1/media/upload.json"
            : $this->baseUrl . $endpoint;
        $headers = $this->oauth->generateHeaders($url, $method);

        $ch = curl_init($url);
        $curlOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10,
        ];

        if ($data && $method !== "GET") {
            if (isset($data["multipart"])) {
                $curlOpts[CURLOPT_POST] = true;
                $curlOpts[CURLOPT_POSTFIELDS] = $data["multipart"]["data"];
                $curlOpts[CURLOPT_HTTPHEADER] = array_filter(
                    $headers,
                    function ($header) {
                        return !str_starts_with($header, "Content-Type:");
                    }
                );
            } else {
                $curlOpts[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($ch, $curlOpts);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \Exception("Curl error: " . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception(
                "API request failed (HTTP {$httpCode}): " . $response
            );
        }

        $decodedResponse = json_decode($response, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception(
                "Invalid JSON response: " . json_last_error_msg()
            );
        }

        return $decodedResponse;
    }
}
