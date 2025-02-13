<?php

namespace codechap\x\Traits;

trait OAuthTrait {
    protected function createSignatureBaseString(array $params, string $url, string $method): string
    {
        ksort($params);
        $paramStr = http_build_query($params);
        $paramStr = str_replace(['+', '%7E'], ['%20', '~'], $paramStr);
        
        return $method . '&' . rawurlencode($url) . '&' . rawurlencode($paramStr);
    }

    protected function generateSignature(string $baseString, string $signingKey): string
    {
        return base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
    }

    protected function buildAuthorizationHeader(array $params): string
    {
        $r = [];
        foreach($params as $key => $value) {
            $r[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }
        return 'OAuth ' . implode(', ', $r);
    }
} 