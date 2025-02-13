<?php

declare(strict_types=1);

namespace codechap\x\Requests;

use codechap\x\Call;

class Post {
    private const MAX_THREAD_LENGTH = 25;
    private const THREAD_DELAY_MS = 500000; // 500ms between thread tweets
    
    /**
     * @var array Supported media types for uploads
     */
    private const SUPPORTED_MEDIA_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    /**
     * @var int Maximum file size in bytes (5MB)
     */
    private const MAX_MEDIA_SIZE = 5242880;

    private $call;

    public function __construct(call $call) 
    {
        $this->call = $call;
    }

    /**
     * Posts a tweet or thread to X/Twitter
     * @param array $message The message to tweet
     * @return array Response data
     * @throws \Exception If tweet posting fails
     */
    public function send($message)
    {
        if (empty($message)) {
            throw new \Exception('Invalid message format');
        }

        if(!is_array($message)) {
            $message = [$message];
        }

        // Determine the type of tweet
        $type = count($message) > 1 ? 'thread' : 'standard';

        switch ($type) {
            case 'standard':
                $params = ["text" => $message[0]->content];
                if (!empty($message[0]->image)) {
                    $pathToImage = $this->validateImage($message[0]->image);
                    $mediaId = $this->uploadMedia($pathToImage);
                    $params['media'] = ['media_ids' => [$mediaId]];
                }
                $result = $this->call->makeRequest('/tweets', $params);
                break;
                
            case 'thread':
                $result = $this->postThread($message);
                break;
                
            default:
                throw new \Exception("Unknown tweet type: {$type}");
        }

        return $result;
    }

    /**
     * Posts a thread of tweets
     * @param array $message The messages to post as thread
     * @return array The last tweet response
     * @throws \Exception If thread posting fails
     */
    private function postThread($message)
    {
        if (count($message) > self::MAX_THREAD_LENGTH) {
            throw new \Exception("Thread exceeds maximum allowed tweets (" . self::MAX_THREAD_LENGTH . ")");
        }

        $previousTweetId = null;
        $lastResult = null;

        foreach ($message as $tweet) {
            if (empty($tweet->content)) {
                continue;
            }

            $params = ["text" => $tweet->content];
            if ($previousTweetId) {
                $params["reply"] = ["in_reply_to_tweet_id" => $previousTweetId];
            }

            // Handle images in thread tweets if present
            if (!empty($tweet->image)) {
                $pathToImage = $this->validateImage($tweet->image);
                $mediaId = $this->uploadMedia($pathToImage);
                $params['media'] = ['media_ids' => [$mediaId]];
            }

            $lastResult = $this->call->makeRequest('/tweets', $params);
            $previousTweetId = $lastResult['data']['id'];

            // Add small delay between tweets to prevent rate limiting
            usleep(self::THREAD_DELAY_MS);
        }

        return $lastResult;
    }

    /**
     * Validates image file before upload
     * @param string $imagePath Path to image file
     * @return string Validated full path to image
     * @throws \Exception If image is invalid
     */
    private function validateImage($imagePath)
    {
        if (!file_exists($imagePath)) {
            throw new \Exception("Image not found: {$imagePath}");
        }

        if (filesize($imagePath) > self::MAX_MEDIA_SIZE) {
            throw new \Exception("Image size exceeds maximum allowed size of 5MB");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath);
        finfo_close($finfo);

        if (!in_array($mimeType, self::SUPPORTED_MEDIA_TYPES)) {
            throw new \Exception("Unsupported image type: {$mimeType}");
        }

        return $imagePath;
    }

    /**
     * Uploads media to X/Twitter
     * @param string $imagePath Path to image file
     * @return string Media ID
     * @throws \Exception If upload fails
     */
    private function uploadMedia($imagePath)
    {
        // Get MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath);
        finfo_close($finfo);

        // Create the CURLFile object
        $media = new \CURLFile($imagePath, $mimeType, 'media');

        // Send the request with just the media parameter
        $result = $this->call->makeRequest('/media/upload', [
            'multipart' => [
                'data' => ['media' => $media]
            ]
        ], 'POST', true);

        if (!isset($result['media_id_string'])) {
            throw new \Exception('Media upload failed: No media ID in response');
        }

        return $result['media_id_string'];
    }
} 