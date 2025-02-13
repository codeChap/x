<?php

require 'vendor/autoload.php';

$apiKey = file_get_contents(realpath(__DIR__ . '/../../') . '/X-API-KEY.txt');
$apiKeySecret = file_get_contents(realpath(__DIR__ . '/../../') . '/X-API-KEY-SECRET.txt');
$accessToken = file_get_contents(sys_get_temp_dir() . '/X-ACCESS-TOKEN.txt');
$accessTokenSecret = file_get_contents(sys_get_temp_dir() . '/X-ACCESS-TOKEN-SECRET.txt');

use codechap\x\X;
use codechap\x\Msg;

$client = new X();
$client->set('apiKey', $apiKey);
$client->set('apiKeySecret', $apiKeySecret);
$client->set('accessToken', $accessToken);
$client->set('accessTokenSecret', $accessTokenSecret);
$userInfo = $client->me();
echo "Hello, " . $userInfo['data']['name'] . "!\n";

// Create thread messages
$threadPartA = new Msg();
$threadPartA->set('content', 'Hello, X!');
$threadPartA->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

$threadPartB = new Msg();
$threadPartB->set('content', 'Hello, X! This is the second message.');
$threadPartB->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

// Single post example
$post = new x();
$post->set('apiKey', $apiKey);
$post->set('apiKeySecret', $apiKeySecret);
$post->set('accessToken', $accessToken);
$post->set('accessTokenSecret', $accessTokenSecret);
$post->post($threadPartA);

// Thread post exmple
$threadPoster = new X();
$threadPoster->set('apiKey', $apiKey);
$threadPoster->set('apiKeySecret', $apiKeySecret);
$threadPoster->set('accessToken', $accessToken);
$threadPoster->set('accessTokenSecret', $accessTokenSecret);
$thread = [$threadPartA, $threadPartB];
$threadPoster->post($thread);
echo "Thread posted successfully!\n";