<?php

declare(strict_types=1);

namespace Crell\HttpTools;

class Point implements HasX
{
    public function __construct(
        public int $x,
        public int $y,
    ) {}
}
