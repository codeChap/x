<?php

declare(strict_types=1);

namespace codechap\x\Requests;

use codechap\x\Call;

class Me {
    private $call;

    public function __construct(Call $call)
    {
        $this->call = $call;
    }

    /**
     * Gets the authenticated user's information
     * @return array Response data
     * @throws \Exception If request fails
     */
    public function get(): array
    {
        return $this->call->makeRequest(
            '/users/me',
            [],
            'GET',
            false
        );
    }
}
