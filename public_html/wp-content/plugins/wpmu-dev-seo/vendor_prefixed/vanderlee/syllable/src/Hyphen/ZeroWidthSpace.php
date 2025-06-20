<?php

namespace Smartcrawl_Vendor\Vanderlee\Syllable\Hyphen;

class ZeroWidthSpace extends Entity
{
    public function __construct()
    {
        parent::__construct('#8203');
    }
}