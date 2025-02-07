<?php

namespace codechap\x;

use codechap\x\Traits\getSet;

class msg {

    use getSet;

    /**
     * @var string
     */
    public string $content;

    /**
     * @var string
     */
    public string $image;
}