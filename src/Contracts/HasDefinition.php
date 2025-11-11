<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Contracts;

use Lsa\Xml\Utils\Validation\Definition;

interface HasDefinition
{
    /**
     * Returns an object as a Definition object.
     * Usually, this method is used on Tags, to register what structure they might have:
     * - Inner tags (and in which occurences)
     * - Attributes
     */
    public function asDefinition(): Definition;
}
