# PHP X (Everything App) API Post Library

A simple PHP library for posting to X (Formerly Twitter) using API v2.

## Installation

```bash
composer require codechap/x:dev-master
```

## Example

```php
<?php

require 'vendor/autoload.php';

use codechap\x\x;
use codechap\x\msg;

// Create thread messages
$threadPartA = new msg();
$threadPartA->set('content', 'Hello, X!');
$threadPartA->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

$threadPartB = new msg();
$threadPartB->set('content', 'Hello, X! This is the second message.');
$threadPartB->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

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
```

```php
<?php

require 'vendor/autoload.php';

use codechap\x\x;
use codechap\x\msg;

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
```

```php
<?php

require 'vendor/autoload.php';

use codechap\x\x;

// Get your user info
$client = new x();
$client->set('apiKey', $apiKey);
$client->set('apiKeySecret', $apiKeySecret);
$client->set('accessToken', $accessToken);
$client->set('accessTokenSecret', $accessTokenSecret);
$client->init();
$userInfo = $client->me();
echo "Hello, " . $userInfo['data']['name'] . "!\n";
```