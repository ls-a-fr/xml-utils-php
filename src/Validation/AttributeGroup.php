<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Collections\TypedAttributeCollection;
use Lsa\Xml\Utils\Exceptions\InvalidElementException;

/**
 * An attribute group is a named list of typed attributes, grouped or not.
 *
 * Heavily inspired from XSD structure.
 */
abstract class AttributeGroup
{
    /**
     * Elements in this group
     *
     * @var list<TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>>
     */
    public readonly array $attributeNames;

    /**
     * Creates a new AttributeGroup
     *
     * @param  list<TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>>  $attributeNames  Elements in this group
     */
    public function __construct(array $attributeNames)
    {
        $this->attributeNames = $attributeNames;
    }

    /**
     * Returns this AttributeGroup as a collection, recursively.
     */
    public function asCollection(): TypedAttributeCollection
    {
        $result = new TypedAttributeCollection();

        return self::asCollectionFrom($result, $this->attributeNames);
    }

    /**
     * Merges recursively any element from this AttributeGroup.
     *
     * @param  TypedAttributeCollection  $result  Result returned and modified for every call
     * @param  list<TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>>|AttributeGroup  $inner  Elements to look into, and add
     * @return TypedAttributeCollection Result from this merge
     */
    private static function asCollectionFrom(
        TypedAttributeCollection $result,
        array|AttributeGroup $inner
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ): TypedAttributeCollection {
        if ($inner instanceof AttributeGroup) {
            $inner = $inner->attributeNames;
        }

        foreach ($inner as $attributeName) {
            if ($attributeName instanceof AttributeGroup) {
                $result->merge(self::asCollectionFrom($result, $attributeName));

                continue;
            }
            if (\is_string($attributeName) === true) {
                $property = PropertyBank::getByClassName($attributeName);
                $result->add($property);

                continue;
            }
            $result->add($attributeName);
        }

        return $result;
    }

    /**
     * Returns an array of attributes, except for the supplied one.
     *
     * @return (AttributeGroup|TypedAttribute|class-string<AttributeGroup|TypedAttribute>)[]
     *
     * @throws InvalidElementException
     */
    public function except(TypedAttribute|string $attribute): array
    {
        $result = [];
        $found = false;
        if (\is_string($attribute) === true) {
            if (\is_subclass_of($attribute, TypedAttribute::class) === false) {
                throw new InvalidElementException('Must provide a TypedAttribute or class name, found: '.$attribute);
            }
            $attribute = PropertyBank::getByClassName($attribute);
        }

        foreach ($this->attributeNames as $attributeOrGroup) {
            // Group handling
            $groupResult = $this->exceptAttributeGroup($attributeOrGroup, $attribute);
            if ($groupResult !== null) {
                $result = array_merge($result, $groupResult);
                $found = true;

                continue;
            }

            // Property handling
            $typedAttributeResult = $this->exceptTypedAttribute($attributeOrGroup, $attribute);
            if ($typedAttributeResult === true) {
                // Property found, ignore it by continuing the loop
                continue;
            } elseif ($typedAttributeResult !== null) {
                // Found
                $result = array_merge($result, $typedAttributeResult);
                $found = true;

                continue;
            }

            // Not found, add to result
            $result[] = $attributeOrGroup;
        }
        if ($found === false) {
            return [$this];
        }

        return $result;
    }

    /**
     * Utility method for except, dedicated to AttributeGroups
     *
     * @param  class-string<AttributeGroup|TypedAttribute>|AttributeGroup|TypedAttribute  $element  Current element
     * @param  string|TypedAttribute  $attribute  Attribute to exclude
     * @return null|((TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>)[])
     */
    private function exceptAttributeGroup(
        string|AttributeGroup|TypedAttribute $element,
        TypedAttribute|string $attribute
    ): ?array {
        if ($element instanceof TypedAttribute || \is_subclass_of($element, TypedAttribute::class) === true) {
            return null;
        }
        if (\is_string($element) === true && \is_subclass_of($element, self::class) === true) {
            $ag = new $element();
            $innerResult = $ag->except($attribute);
            if (isset($innerResult[0]) === true && $innerResult[0] !== $ag) {
                return $innerResult;
            }
        }
        if ($element instanceof AttributeGroup) {
            $innerResult = $element->except($attribute);
            if (isset($innerResult[0]) === true && $innerResult[0] !== $element) {
                return $innerResult;
            }
        }

        return null;
    }

    /**
     * Utility method for except, dedicated to TypedAttributes
     *
     * @param  class-string<AttributeGroup|TypedAttribute>|AttributeGroup|TypedAttribute  $element  Current element
     * @param  string|TypedAttribute  $attribute  Attribute to exclude
     * @return null|((TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>)[])
     */
    private function exceptTypedAttribute(
        string|TypedAttribute|AttributeGroup $element,
        string|TypedAttribute $attribute
    ): array|true|null {
        if ($element instanceof AttributeGroup || \is_subclass_of($element, self::class) === true) {
            return null;
        }
        if (\is_string($element) === true && \is_subclass_of($element, TypedAttribute::class) === true) {
            // Found without need to create property, cast etc
            $attributeClass = $attribute;
            if (\is_object($attribute) === true) {
                $attributeClass = \get_class($attribute);
            }
            if ($element === $attributeClass) {
                return true;
            }
            $property = PropertyBank::getByClassName($element);
        } else {
            $property = $element;
        }
        if ($property === $attribute) {
            // Found
            return true;
        }
        if ($property instanceof ShorthandTypedAttribute || $property instanceof CompoundTypedAttribute) {
            // Will do nothing if property does not contain specified attribute
            if (\is_string($attribute) === true) {
                if (\is_subclass_of($attribute, TypedAttribute::class) === true) {
                    $attribute = PropertyBank::getByClassName($attribute);
                } else {
                    $attribute = PropertyBank::getOne($attribute);
                }
            }
            $innerResult = $property->except($attribute);
            if (isset($innerResult[0]) === true && $innerResult[0] !== $property) {
                return $innerResult;
            }
        }

        return null;
    }
}
