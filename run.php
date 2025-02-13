<?php

require 'vendor/autoload.php';

$apiKey = file_get_contents(realpath(__DIR__ . '/../../') . '/X-API-KEY.txt');
$apiKeySecret = file_get_contents(realpath(__DIR__ . '/../../') . '/X-API-KEY-SECRET.txt');
$accessToken = file_get_contents(realpath(__DIR__ . '/../../') . '/X-ACCESS-TOKEN.txt');
$accessTokenSecret = file_get_contents(realpath(__DIR__ . '/../../') . '/X-ACCESS-TOKEN-SECRET.txt');

use codechap\x\x;
use codechap\x\msg;
use codechap\x\Requests\me;


$client = new x();
$client->set('apiKey', $apiKey);
$client->set('apiKeySecret', $apiKeySecret);
$client->set('accessToken', $accessToken);
$client->set('accessTokenSecret', $accessTokenSecret);
$client->init();
$userInfo = $client->me();
echo "Hello, " . $userInfo['data']['name'] . "!\n";

die();

// Create thread messages
$threadPartA = new msg();
$threadPartA->set('content', 'Hello, X!');
$threadPartA->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

$threadPartB = new msg();
$threadPartB->set('content', 'Hello, X! This is the second message.');
$threadPartB->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

// Single post example
$post = new x();
$post->set('apiKey', $apiKey);
$post->set('apiKeySecret', $apiKeySecret);
$post->set('accessToken', $accessToken);
$post->set('accessTokenSecret', $accessTokenSecret);
$post->init();

$msg = new msg();
$msg->set('content', 'Hello, X!');
$msg->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

$post->post($msg);
echo "Single post successful!";

// Initialize X client for thread
$threadPoster = new x();
$threadPoster->set('apiKey', $apiKey);
$threadPoster->set('apiKeySecret', $apiKeySecret);
$threadPoster->set('accessToken', $accessToken);
$threadPoster->set('accessTokenSecret', $accessTokenSecret);
$threadPoster->init();

// Post thread
$thread = [$threadPartA, $threadPartB];
$threadPoster->post($thread);
echo "Thread posted successfully!\n";