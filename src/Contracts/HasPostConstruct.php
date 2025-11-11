<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Contracts;

interface HasPostConstruct
{
    public function postConstruct(): void;
}
