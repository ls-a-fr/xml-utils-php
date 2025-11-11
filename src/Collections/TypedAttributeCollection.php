<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Collections;

use Lsa\Xml\Utils\Exceptions\InvalidCollectionOperationException;
use Lsa\Xml\Utils\Validation\ShorthandTypedAttribute;
use Lsa\Xml\Utils\Validation\TypedAttribute;

/**
 * Collection of TypedAttributes
 *
 * @extends Collection<TypedAttribute>
 */
class TypedAttributeCollection extends Collection
{
    /**
     * Add supplied attribute in this Collection.
     *
     * @param  TypedAttribute  $attribute  Attribute to add
     *
     * @throws InvalidCollectionOperationException
     */
    public function add($attribute): static
    {
        if (
            $this->has($attribute) === true
            && (($attribute instanceof ShorthandTypedAttribute) === false || $attribute->hasMainAttribute() === true)
        ) {
            // No operation
            return $this;
        }
        /**
         * Not sure to consider PHPDoc as certain here
         *
         * @phpstan-ignore instanceof.alwaysTrue
         */
        if ($attribute instanceof TypedAttribute) {
            parent::add($attribute);

            return $this;
        }
        /**
         * Not sure to consider PHPDoc as certain here.
         *
         * @phpstan-ignore deadCode.unreachable
         */
        if (\is_string($attribute) === true) {
            $string = $attribute;
        } else {
            $string = \get_class($attribute);
        }
        throw new InvalidCollectionOperationException(
            'Attributes must be Typed, Shorthand or Compound. Found: '.$string
        );
    }

    /**
     * Add listed attributes in this Collection.
     *
     * @param  list<TypedAttribute>  $attributes  Attributes to add
     *
     * @throws InvalidCollectionOperationException
     */
    public function addAll($attributes): static
    {
        /**
         * Not sure to consider PHPDoc as certain here.
         *
         * @phpstan-ignore function.alreadyNarrowedType, identical.alwaysFalse
         */
        if (\is_iterable($attributes) === false) {
            throw new InvalidCollectionOperationException('Attributes must be at least iterable');
        }
        foreach ($attributes as $attribute) {
            $this->add($attribute);
        }

        return $this;
    }

    public function get(string $type): static
    {
        return $this->filter(fn ($t) => $t->type === $type);
    }

    public function getByName(string $name): static
    {
        return $this->filter(fn ($t) => $t->name == $name);
    }

    public function has($element): bool
    {
        if ($element instanceof TypedAttribute) {
            $found = $this->filter(fn ($a) => $a->name === $element->name && $a->type === $element->type)->first();

            return $found !== null;
        }

        return parent::has($element);
    }

    /**
     * Gets unique attributes for this Collection
     */
    public function unique(): TypedAttributeCollection
    {
        $result = new TypedAttributeCollection();
        foreach ($this->data as $typedAttribute) {
            if ($result->has($typedAttribute) === true) {
                continue;
            }
            $result->add($typedAttribute);
        }

        return $result;
    }
}
