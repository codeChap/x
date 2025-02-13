<?php

$apiKey = file_get_contents(realpath(__DIR__ . '/../../') . '/X-API-KEY.txt');
$apiKeySecret = file_get_contents(realpath(__DIR__ . '/../../') . '/X-API-KEY-SECRET.txt');
$callbackUrl = 'http://localhost:8080/callback';
require 'vendor/autoload.php';

use codechap\x\X;

$c = new X();
$c->set('apiKey', $apiKey);
$c->set('apiKeySecret', $apiKeySecret);
$authUrl = $c->getAuthUrlFor($callbackUrl);
print 'Please visit this URL to login: ' . $authUrl . PHP_EOL;

echo "After authenticating, please paste the full callback URL here: ";
$url = trim(fgets(STDIN));

if (empty($url)) {
    die("No URL provided.\n");
}

parse_str(parse_url($url, PHP_URL_QUERY), $params);

if (!isset($params['oauth_token']) || !isset($params['oauth_verifier'])) {
    die("Invalid callback URL. Missing required OAuth parameters.\n");
}

$r = $c->handleCallback($params['oauth_token'], $params['oauth_verifier'], $callbackUrl);

// Save tokens to files
file_put_contents(sys_get_temp_dir() . '/X-ACCESS-TOKEN.txt', $r['access_token']);
file_put_contents(sys_get_temp_dir() . '/X-ACCESS-TOKEN-SECRET.txt', $r['access_token_secret']);

echo "Authentication successful! Access tokens have been saved.\n";

?>