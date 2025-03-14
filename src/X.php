<?php

declare(strict_types=1);

namespace codechap\x;

use codechap\x\Traits\GetSet;
use codechap\x\Requests\Post;
use codechap\x\Requests\Me;
use codechap\x\Auth\XAuth;
use codechap\x\Call;

class X
{
    use GetSet;

    public string $apiKey;
    public string $apiKeySecret;
    public string $accessToken;
    public string $accessTokenSecret;

    /**
     * Get the authentication URL for the given callback URL.
     *
     * @param string $callbackUrl The callback URL to use.
     * @return string The authentication URL.
     */
    public function getAuthUrlFor($callbackUrl): string
    {
        $auth = new XAuth($this->apiKey, $this->apiKeySecret, $callbackUrl);
        return $auth->getAuthUrl();
    }

    /**
     * Handle the callback from the authentication URL.
     *
     * @param string $oauthToken The OAuth token.
     * @param string $oauthVerifier The OAuth verifier.
     * @param string $callbackUrl The callback URL to use.
     * @return array The access token and access token secret.
     */
    public function handleCallback($oauthToken, $oauthVerifier, $callbackUrl) : array
    {
        $auth = new XAuth($this->apiKey, $this->apiKeySecret, $callbackUrl);
        return $auth->handleCallback($oauthToken, $oauthVerifier);
    }

    /**
     * Get the user's profile information.
     *
     * @return array The user's profile information.
     */
    public function me(): array
    {
        $call = new Call();
        $call->set("apiKey", $this->apiKey);
        $call->set("apiKeySecret", $this->apiKeySecret);
        $call->set("accessToken", $this->accessToken);
        $call->set("accessTokenSecret", $this->accessTokenSecret);
        $call->init();
        $meRequest = new Me($call);
        return $meRequest->get();
    }

    /**
     * Post a message to the user's timeline.
     *
     * @param string|array $message The message to post.
     * @return array The response from the API.
     */
    public function post($message) : array
    {
        $call = new Call();
        $call->set("apiKey", $this->apiKey);
        $call->set("apiKeySecret", $this->apiKeySecret);
        $call->set("accessToken", $this->accessToken);
        $call->set("accessTokenSecret", $this->accessTokenSecret);
        $call->init();
        $poster = new Post($call);
        return $poster->send($message);
    }
}
