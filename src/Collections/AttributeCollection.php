<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Collections;

use Lsa\Xml\Utils\Exceptions\InvalidCollectionOperationException;
use Lsa\Xml\Utils\Xml\Attribute;

/**
 * Collection of Attributes
 *
 * @extends Collection<Attribute>
 */
class AttributeCollection extends Collection
{
    /**
     * Adds all supplied elements in the current Collection.
     *
     * @param  list<Attribute>|array<string,string|list<string>>|AttributeCollection  $attributes  Attributes to add
     *
     * @throws InvalidCollectionOperationException Bad format for input $attributes
     */
    public function addAll($attributes): static
    {
        foreach ($attributes as $attributeName => $attributeValue) {
            if ($attributeValue instanceof Attribute) {
                $this->add($attributeValue);

                continue;
            }
            if (\is_string($attributeName) === false) {
                throw new InvalidCollectionOperationException(
                    'When using addAll with an array, attribute names must be supplied as keys and 
                    values as values'
                );
            }

            if (\is_array($attributeValue) === true) {
                $attributeValue = \implode($attributeValue);
            }

            $this->add(new Attribute($attributeName, $attributeValue));
        }

        return $this;
    }

    /**
     * Removes an attribute based on its name
     *
     * @param  string  $name  Attribute name
     */
    public function removeByName(string $name): static
    {
        foreach ($this->data as $k => $attribute) {
            if ($attribute->name === $name) {
                unset($this->data[$k]);
                break;
            }
        }

        return $this;
    }

    /**
     * Gets an attribute based on its name
     *
     * @param  string  $name  The attribute name
     * @param  ?string  $defaultValue  Default value when nonexistant (default is null)
     * @return ?string The found value, or default value otherwise.
     */
    public function get(string $name, ?string $defaultValue = null): ?string
    {
        foreach ($this->data as $attribute) {
            if ($attribute->name === $name) {
                return $attribute->value;
            }
        }

        return $defaultValue;
    }
}
