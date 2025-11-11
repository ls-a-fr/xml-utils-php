<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Collections\TypedAttributeCollection;
use Lsa\Xml\Utils\Contracts\HasPostConstruct;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Xml\NcNameType;
use RuntimeException;

/**
 * A ShorthandTypeAttribute is a shorthand attribute, common in XML (HTML, FOP, ...)
 * For example: "margin" in HTML refers to "margin-top", "margin-left", "margin-right" and "margin-bottom".
 *
 * To create a ShorthandTypeAttribute, you will need:
 * - A "main" attribute (which sometimes does not exist. Example of this: "border-after" in XSL-FO)
 * - A list of attributes linked to the main one.
 *
 * In the "margin" example, that would lead to:
 * ```php
 * <?php
 * class Margin extends ShorthandTypedAttribute
 * {
 *     public function __construct($required = false)
 *     {
 *         parent::__construct(
 *             'margin'
 *             MarginType::class
 *             $required,
 *             [
 *                 new TypedAttribute('margin-top', MarginWidthType::class),
 *                 new TypedAttribute('margin-bottom', MarginWidthType::class),
 *                 new TypedAttribute('margin-left', MarginWidthType::class),
 *                 new TypedAttribute('margin-right', MarginWidthType::class),
 *             ]
 *         );
 *     }
 * }
 * ```
 */
class ShorthandTypedAttribute extends TypedAttribute implements HasPostConstruct
{
    /**
     * Does this shorthand has a main attribute
     */
    private bool $mainAttribute = true;

    /**
     * Before build, list of inner properties
     *
     * @var list<class-string<TypedAttribute>|TypedAttribute>
     */
    private readonly array $innerAttributeClassNames;

    /**
     * The linked attributes
     */
    private readonly TypedAttributeCollection $attributes;

    /**
     * Flag for post-construct call
     */
    private bool $postConstructed = false;

    /**
     * Creates a new ShorthandTypedAttribute
     *
     * @param  ?string  $name  Principal attribute name for this shorthand, ie "margin" for "margin-xxx".
     * @param  ?class-string<Type>  $type  Principal attribute name for this shorthand, ie "MarginType"
     * @param  list<TypedAttribute|class-string<TypedAttribute>>|TypedAttributeCollection  $attributes  Associated attributes
     *
     * @throws RuntimeException
     */
    protected function __construct(
        ?string $name = null,
        ?string $type = null,
        array|TypedAttributeCollection|null $attributes = null
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ) {
        if ($this->mainAttribute === true) {
            if ($name === null || $type === null) {
                throw new RuntimeException(
                    'Must supply main TypedAttribute, except if you call noMainAttribute() first'
                );
            }
            parent::__construct($name, $type);
        } else {
            parent::__construct('', NcNameType::class);
        }

        if ($attributes instanceof TypedAttributeCollection) {
            $this->attributes = $attributes;
            $this->innerAttributeClassNames = [];
        } else {
            $this->attributes = new TypedAttributeCollection();
            if ($attributes === null) {
                $attributes = [];
            }
            $this->innerAttributeClassNames = $attributes;
        }
    }

    /**
     * Gets inner properties in this shorthand
     */
    public function getAttributes(): TypedAttributeCollection
    {
        if ($this->postConstructed === false) {
            $this->postConstruct();
        }

        return $this->attributes;
    }

    public function postConstruct(): void
    {
        if ($this->postConstructed === true) {
            return;
        }
        if (empty($this->innerAttributeClassNames) === false) {
            foreach ($this->innerAttributeClassNames as $attributeOrClassName) {
                if (\is_string($attributeOrClassName) === true) {
                    $property = PropertyBank::getByClassName($attributeOrClassName);
                    if ($property instanceof HasPostConstruct) {
                        $property->postConstruct();
                    }
                    $this->attributes->add($property);
                } else {
                    $this->attributes->add($attributeOrClassName);
                }
            }
        }
        $this->postConstructed = true;
    }

    /**
     * Declares this ShorthandTypedAttribute has no main attribute
     */
    protected function noMainAttribute(): void
    {
        $this->mainAttribute = false;
    }

    /**
     * Checks if this ShorthandTypedAttribute has a main attribute
     */
    public function hasMainAttribute(): bool
    {
        if ($this->postConstructed === false) {
            $this->postConstruct();
        }

        return $this->mainAttribute;
    }

    public function unpack(): array
    {
        $unpacked = [];
        if ($this->hasMainAttribute() === true) {
            $unpacked[$this->name] = $this->type;
        }
        foreach ($this->getAttributes() as $attribute) {
            $unpacked = array_merge($unpacked, $attribute->unpack());
        }

        return $unpacked;
    }

    /**
     * Flatten this ShorthandTypedAttribute in a TypedAttributeCollection
     */
    public function flatten(): TypedAttributeCollection
    {
        $flatten = new TypedAttributeCollection();
        if ($this->hasMainAttribute() === true) {
            $flatten->add(new TypedAttribute($this->name, $this->type));
        }
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
        if ($this->hasMainAttribute() === true) {
            if ($this->name === $attribute->name && $this->type === $attribute->type) {
                // Return everything except main
                return $this->getAttributes()->toArray();
            }
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
            if ($attribute->name === $a->name && $attribute->type === $a->type) {
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
