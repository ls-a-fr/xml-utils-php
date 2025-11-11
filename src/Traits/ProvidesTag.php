<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

use Lsa\Xml\Utils\Xml\Attribute;
use Lsa\Xml\Utils\Xml\XmlNamespace;

/**
 * Allows to shape the object in an XML tag form.
 * A tag may have a prefix, a local name, and attributes.
 */
trait ProvidesTag
{
    use ProvidesAttributes;

    /**
     * The tag prefix (ex: "fo" in "fo:block")
     */
    public string $prefix;

    /**
     * The local name (ex: "block" in "fo:block")
     */
    public readonly string $localName;

    /**
     * Renders the tag name
     */
    abstract public function getTagName(): string;

    /**
     * Adds a namespace in this tag
     *
     * @param  \Lsa\Xml\Utils\Xml\XmlNamespace  $namespace  The namespace
     */
    public function namespace(XmlNamespace $namespace): static
    {
        $this->attributes->add(new Attribute('xmlns:'.$namespace->prefix, $namespace->uri));

        return $this;
    }

    /**
     * Adds a several namespaces in this tag
     *
     * @param  \Lsa\Xml\Utils\Xml\XmlNamespace[]  $namespaces  The namespaces
     */
    public function namespaces(array $namespaces): static
    {
        foreach ($namespaces as $attribute) {
            $this->namespace($attribute);
        }

        return $this;
    }
}
