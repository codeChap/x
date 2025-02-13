<?php

declare(strict_types=1);

namespace codechap\x;

use codechap\x\Traits\GetSet;
use codechap\x\Requests\Post;
use codechap\x\Requests\Me;
use codechap\x\Auth\XAuth;
use codechap\x\Call;

class X {
    use GetSet;

    public string $apiKey;
    public string $apiKeySecret;
    public string $accessToken;
    public string $accessTokenSecret;

    public function getAuthUrlFor($callbackUrl): string
    {
        $auth = new XAuth($this->apiKey, $this->apiKeySecret, $callbackUrl);
        return $auth->getAuthUrl();
    }

    public function handleCallback($oauthToken, $oauthVerifier, $callbackUrl)
    {
        $auth = new XAuth($this->apiKey, $this->apiKeySecret, $callbackUrl);
        return $auth->handleCallback($oauthToken, $oauthVerifier);
    }

    public function me(): array
    {
        $call = new Call();
        $call->set('apiKey', $this->apiKey);
        $call->set('apiKeySecret', $this->apiKeySecret);
        $call->set('accessToken', $this->accessToken);
        $call->set('accessTokenSecret', $this->accessTokenSecret);
        $call->init();
        $meRequest = new Me($call);
        return $meRequest->get();
    }

    public function post($message)
    {
        $call = new Call();
        $call->set('apiKey', $this->apiKey);
        $call->set('apiKeySecret', $this->apiKeySecret);
        $call->set('accessToken', $this->accessToken);
        $call->set('accessTokenSecret', $this->accessTokenSecret);
        $call->init();
        $poster = new Post($call);
        return $poster->send($message);
    }
}