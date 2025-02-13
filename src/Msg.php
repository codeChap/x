<?php

namespace codechap\x;

use codechap\x\Traits\GetSet;

class Msg {

    use GetSet;

    /**
     * @var string
     */
    public string $content;

    /**
     * @var string
     */
    public string $image;
}