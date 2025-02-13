<?php

declare(strict_types=1);

namespace codechap\x\Requests;

use codechap\x\x;

class me {
    private $client;

    public function __construct(x $client) 
    {
        $this->client = $client;
    }

    /**
     * Gets the authenticated user's information
     * @return array Response data
     * @throws \Exception If request fails
     */
    public function get(): array
    {
        return $this->client->makeRequest(
            '/users/me',
            [],
            'GET',
            3,
            false
        );
    }
}
