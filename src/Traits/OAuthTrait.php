<?php

namespace codechap\x\Traits;

trait OAuthTrait
{
    /**
     * Creates the signature base string for OAuth authentication.
     *
     * @param array $params The parameters to include in the signature base string.
     * @param string $url The URL to include in the signature base string.
     * @param string $method The HTTP method to include in the signature base string.
     * @return string The signature base string.
     */
    protected function createSignatureBaseString(
        array $params,
        string $url,
        string $method
    ): string {
        ksort($params);
        $paramStr = http_build_query($params);
        $paramStr = str_replace(["+", "%7E"], ["%20", "~"], $paramStr);

        return $method .
            "&" .
            rawurlencode($url) .
            "&" .
            rawurlencode($paramStr);
    }

    /**
     * Generates the signature for OAuth authentication.
     *
     * @param string $baseString The base string to generate the signature from.
     * @param string $signingKey The signing key to use for generating the signature.
     * @return string The generated signature.
     */
    protected function generateSignature(
        string $baseString,
        string $signingKey
    ): string {
        return base64_encode(hash_hmac("sha1", $baseString, $signingKey, true));
    }

    /**
     * Builds the authorization header for OAuth authentication.
     *
     * @param array $params The parameters to include in the authorization header.
     * @return string The authorization header.
     */
    protected function buildAuthorizationHeader(array $params): string
    {
        $r = [];
        foreach ($params as $key => $value) {
            $r[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }
        return "OAuth " . implode(", ", $r);
    }
}
