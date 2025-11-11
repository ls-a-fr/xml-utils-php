<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

use Lsa\Xml\Utils\Collections\AttributeCollection;
use Lsa\Xml\Utils\Xml\Attribute;

/**
 * Grants attributes to an XmlNode
 */
trait ProvidesAttributes
{
    /**
     * Attributes stored in this node
     */
    public readonly AttributeCollection $attributes;

    /**
     * Adds an attribute to this node
     *
     * @param  \Lsa\Xml\Utils\Xml\Attribute  $attribute  Added attribute
     * @return static Current node
     */
    public function attribute(Attribute $attribute): static
    {
        $this->attributes->add($attribute);

        return $this;
    }

    /**
     * Adds several attributes to this node
     *
     * @param  list<\Lsa\Xml\Utils\Xml\Attribute>|\Lsa\Xml\Utils\Collections\AttributeCollection  $attributes  Added attributes
     * @return static Current node
     */
    public function attributes(array|AttributeCollection $attributes): static
    {
        foreach ($attributes as $attribute) {
            $this->attribute($attribute);
        }

        return $this;
    }
}
