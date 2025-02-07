# PHP X (Everything App) API Posting Library

A simple PHP library for posting to X (Twitter) using API v2.

## Installation

composer require codechap/x

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

That's it! Check the source code for more features like posting threads and handling media uploads.

## Features

- OAuth 1.0a authentication
- Post single tweets
- Create tweet threads (up to 25 tweets)
- Upload media (images)
- Automatic retry mechanism for failed requests
- Rate limiting protection
- Comprehensive error handling

## Requirements

- PHP 8.2 or higher