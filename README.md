# PHP X (Everything App) API Post Library

A simple PHP library for posting to X (Formerly Twitter) using the free tier.

## Installation

```bash
composer require codechap/x:dev-master
```

## Example

```php
<?php

require 'vendor/autoload.php';

use codechap\x\X;
use codechap\x\Msg;

// Create thread messages
$threadPartA = new Msg();
$threadPartA->set('content', 'Hello, X!');
$threadPartA->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

$threadPartB = new Msg();
$threadPartB->set('content', 'Hello, X! This is the second message.');
$threadPartB->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

// Initialize X client for thread
$threadPoster = new X();
$threadPoster->set('apiKey', $apiKey);
$threadPoster->set('apiKeySecret', $apiKeySecret);
$threadPoster->set('accessToken', $accessToken);
$threadPoster->set('accessTokenSecret', $accessTokenSecret);

// Post thread
$thread = [$threadPartA, $threadPartB];
$threadPoster->post($thread);
echo "Thread posted successfully!\n";
```

```php
<?php

require 'vendor/autoload.php';

use codechap\x\X;
use codechap\x\Msg;

// Single post example
$post = new X();
$post->set('apiKey', $apiKey);
$post->set('apiKeySecret', $apiKeySecret);
$post->set('accessToken', $accessToken);
$post->set('accessTokenSecret', $accessTokenSecret);

$msg = new Msg();
$msg->set('content', 'Hello, X!');
$msg->set('image', 'img-HrW6drkAzh4UqUaXD3o3H.jpeg');

$post->post($msg);
echo "Single post successful!";
```

```php
<?php

require 'vendor/autoload.php';

use codechap\x\X;

// Get your user info
$client = new X();
$client->set('apiKey', $apiKey);
$client->set('apiKeySecret', $apiKeySecret);
$client->set('accessToken', $accessToken);
$client->set('accessTokenSecret', $accessTokenSecret);
$userInfo = $client->me();
echo "Hello, " . $userInfo['data']['name'] . "!\n";
```
