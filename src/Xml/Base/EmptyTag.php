<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml\Base;

use Lsa\Xml\Utils\Collections\AttributeCollection;
use Lsa\Xml\Utils\Collections\NodeCollection;
use Lsa\Xml\Utils\Traits\ProvidesPrefixDetection;
use Lsa\Xml\Utils\Traits\ProvidesTag;

/**
 * An EmptyTag is a Node that can have attributes, but no child.
 */
abstract class EmptyTag extends Node
{
    use ProvidesPrefixDetection;
    use ProvidesTag;

    /**
     * Creates an new EmptyTag
     *
     * @param  AttributeCollection  $attributes  Attributes of this tag. Defaults to a new empty Collection
     */
    public function __construct(AttributeCollection $attributes = new AttributeCollection())
    {
        $this->attributes = $attributes;

        [$prefix, $name] = self::splitPrefixAndName($this->getTagName());

        $this->prefix = $prefix;
        $this->localName = $name;
    }

    public function getContent(): NodeCollection|string|null
    {
        return null;
    }
}
