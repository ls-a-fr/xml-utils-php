<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Collections\TypedAttributeCollection;
use Lsa\Xml\Utils\Validation\Base\Type;

/**
 * A CompoundTypedAttribute refers to multi-properties in attributes.
 * Less common in HTML (has it any?), it can be used in other XML derivatives such as XSL-FO.
 *
 * Example: In XSL-FO, a property called 'block-progression-dimension' registers three inner values:
 * - block-progression-dimension.minimum
 * - block-progression-dimension.optimum
 * - block-progression-dimension.maximum
 *
 * This attribute would be written as:
 * ```php
 * <?php
 * class BlockProgressionDimension extends CompoundTypedAttribute
 * {
 *     public function __construct($required = false)
 *     {
 *         parent::__construct(
 *             'block-progression-dimension',
 *             BlockProgressionDimensionType::class,
 *             $required,
 *             [
 *                 new TypedAttribute('block-progression-dimension.minimum', LengthType::class),
 *                 new TypedAttribute('block-progression-dimension.optimum', BlockProgressionDimensionOptimumType::class),
 *                 new TypedAttribute('block-progression-dimension.maximum', BlockProgressionDimensionMaximumType::class),
 *             ]
 *         );
 *     }
 * }
 * ```
 */
class CompoundTypedAttribute extends TypedAttribute
{
    /**
     * The linked attributes
     */
    public readonly TypedAttributeCollection $attributes;

    /**
     * Creates a new CompoundTypedAttribute
     *
     * @param  string  $name  Principal typed attribute for this compound, ie "keep-together" for "keep-together.within-column".
     * @param  class-string<Type>  $type  Type used to validate this compound attribute.
     * @param  array<string,class-string<Type>>|list<TypedAttribute>|TypedAttributeCollection  $attributes  Associated attributes
     */
    protected function __construct(
        string $name,
        string $type,
        array|TypedAttributeCollection $attributes
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ) {
        parent::__construct($name, $type);
        if ($attributes instanceof TypedAttributeCollection) {
            $this->attributes = $attributes;
        } else {
            if (\array_is_list($attributes) === true) {
                /**
                 * PHPStan is wrong here: As this array is a list, it can't be an array<string, class-string<Type>>
                 *
                 * @phpstan-ignore argument.type
                 */
                $this->attributes = (new TypedAttributeCollection())->addAll($attributes);
            } else {
                $generatedAttributes = new TypedAttributeCollection();
                foreach ($attributes as $compoundName => $typeClass) {
                    /**
                     * Same here, PHPStan is wrong. Assert to prevent errors
                     */
                    assert(\is_string($compoundName));
                    assert(\is_subclass_of($typeClass, Type::class));
                    $generatedAttributes->add(new TypedAttribute($this->name.'.'.$compoundName, $typeClass));
                }
                $this->attributes = $generatedAttributes;
            }
        }
    }

    /**
     * Gets inner properties in this compound
     */
    public function getAttributes(): TypedAttributeCollection
    {
        return $this->attributes;
    }

    /**
     * CompoundTypedAttribute always has a main attribute
     */
    public function hasMainAttribute(): true
    {
        return true;
    }

    public function unpack(): array
    {
        $unpacked = [];
        $unpacked[$this->name] = $this->type;

        foreach ($this->getAttributes() as $attribute) {
            $unpacked = array_merge($unpacked, $attribute->unpack());
        }

        return $unpacked;
    }

    /**
     * Flatten this CompoundTypedAttribute in a TypedAttributeCollection
     */
    public function flatten(): TypedAttributeCollection
    {
        $flatten = new TypedAttributeCollection();
        $flatten->add(new TypedAttribute($this->name, $this->type));
        foreach ($this->getAttributes() as $attribute) {
            if ($attribute instanceof ShorthandTypedAttribute || $attribute instanceof CompoundTypedAttribute) {
                $flatten->merge($attribute->flatten());
            } else {
                $flatten->add($attribute);
            }
        }

        return $flatten;
    }

    /**
     * Returns an array of attributes, except for the supplied one.
     *
     * @return TypedAttribute[]
     */
    public function except(TypedAttribute $attribute): array
    {
        $result = [];
        $found = false;
        if ($this->name === $attribute->name && $this->type === $attribute->type) {
            // Return everything except main
            return $this->getAttributes()->toArray();
        }
        foreach ($this->getAttributes() as $a) {
            if ($a instanceof ShorthandTypedAttribute || $a instanceof CompoundTypedAttribute) {
                $innerResult = $a->except($attribute);
                if (isset($innerResult[0]) === false || $innerResult[0] !== $a) {
                    $found = true;
                }
                $result = array_merge($result, $innerResult);

                continue;
            }
            // Simple TypedAttribute
            if ($this->name === $a->name && $this->type === $a->type) {
                // Found
                $found = true;

                continue;
            }
            $result[] = $a;
        }
        if ($found === false) {
            return [$this];
        }

        return $result;
    }
}
