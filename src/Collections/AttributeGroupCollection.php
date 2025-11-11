<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Collections;

use Lsa\Xml\Utils\Exceptions\InvalidCollectionOperationException;
use Lsa\Xml\Utils\Traits\HasName;
use Lsa\Xml\Utils\Traits\UsesCache;
use Lsa\Xml\Utils\Validation\AttributeGroup;
use Lsa\Xml\Utils\Validation\CompoundTypedAttribute;
use Lsa\Xml\Utils\Validation\PropertyBank;
use Lsa\Xml\Utils\Validation\ShorthandTypedAttribute;
use Lsa\Xml\Utils\Validation\TypedAttribute;

/**
 * Collection of AttributeGroup
 *
 * @extends Collection<AttributeGroup>
 */
class AttributeGroupCollection extends Collection
{
    use UsesCache;

    /**
     * Adds an AttributeGroup to this Collection.
     *
     * @param  AttributeGroup  $group
     */
    public function add($group): static
    {
        $this->data[get_class($group)] = $group;

        return $this;
    }

    /**
     * Removes an AttributeGroup from this Collection, by its class name.
     *
     * @param  class-string<AttributeGroup>  $className
     */
    public function removeByName(string $className): static
    {
        if ($this->has($className) === true) {
            unset($this->data[$className]);
        }

        return $this;
    }

    /**
     * Checks if the supplied element is inside the current Collection.
     *
     * @param  class-string<AttributeGroup>|AttributeGroup  $classNameOrGroup  ClassName or AttributeGroup instance
     * @return bool True if found, false otherwise.
     *
     * @throws InvalidCollectionOperationException
     */
    public function has($classNameOrGroup): bool
    {
        /**
         * Not so sure to treat PHPDoc as certain in this specific context.
         *
         * @phpstan-ignore instanceof.alwaysTrue, booleanAnd.alwaysFalse, identical.alwaysFalse
         */
        if (is_string($classNameOrGroup) === false && ($classNameOrGroup instanceof AttributeGroup) === false) {
            throw new InvalidCollectionOperationException(
                'Method has must be used with a class name or an AttributeGroup instance'
            );
        }
        if (is_string($classNameOrGroup) === false) {
            /**
             * I don't know why phpstan thinks class_uses returns string[] and not false|string[].
             * Plus PHPDoc on PHP Core is incoherent with PHP website.
             *
             * @var array<string, class-string>|false $classesUsed
             */
            $classesUsed = \class_uses($classNameOrGroup);
            if ($classesUsed !== false && \in_array(HasName::class, $classesUsed) === true) {
                /**
                 * This class uses trait HasName, so it has a name property.
                 * Two steps to prevent phpstan complaining
                 *
                 * @var \Lsa\Xml\Utils\Traits\HasName $classNameOrGroup
                 *
                 * @phpstan-ignore varTag.nativeType, varTag.trait, class.notFound
                 */
                $elementClassName = $classNameOrGroup->name;
                $classNameOrGroup = $elementClassName;
            } else {
                $classNameOrGroup = get_class($classNameOrGroup);
            }
        }

        return isset($this->data[$classNameOrGroup]);
    }

    /**
     * Returns an AttributeGroup based on its class name.
     *
     * @param  class-string<AttributeGroup>  $className  AttributeGroup to search for
     * @param  ?AttributeGroup  $defaultValue  AttributeGroup to get if the search fails. Default to null.
     */
    public function get(string $className, ?AttributeGroup $defaultValue = null): ?AttributeGroup
    {
        if ($this->has($className) === true) {
            return $this->data[$className];
        }

        return $defaultValue;
    }

    /**
     * Checks if this Collection includes a specific TypedAttribute.
     *
     * @param  TypedAttribute  $attribute  Attribute to check
     * @return bool True if this Collection includes the specified attribute, false otherwise.
     */
    public function includes(TypedAttribute $attribute): bool
    {
        foreach ($this->data as $attributeGroup) {
            if ($attributeGroup->asCollection()->has($attribute) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns all attributes allowed in this AttributeGroup, recursively.
     * Be careful, this also breaks Compound and Shorthand as TypedAttributes.
     *
     * @return TypedAttributeCollection $attributes
     */
    public function getAllAttributes(): TypedAttributeCollection
    {
        $result = $this->fromCache(\spl_object_hash($this), function () {
            $list = new TypedAttributeCollection();
            foreach ($this->data as $group) {
                foreach ($group->attributeNames as $attributeOrGroup) {
                    // AttributeGroup handling
                    if (
                        \is_string($attributeOrGroup) === true
                        && \is_subclass_of($attributeOrGroup, AttributeGroup::class) === true
                    ) {
                        $attributeOrGroup = new $attributeOrGroup();
                    }
                    if ($attributeOrGroup instanceof AttributeGroup) {
                        $list->merge($attributeOrGroup->asCollection());

                        continue;
                    }
                    // TypedAttribute handling
                    if (\is_string($attributeOrGroup) === true) {
                        if (\is_subclass_of($attributeOrGroup, TypedAttribute::class) === true) {
                            $attributeOrGroup = PropertyBank::getByClassName($attributeOrGroup);
                        } else {
                            throw new InvalidCollectionOperationException(
                                'Must be a subclass of TypedAttribute, found: '.$attributeOrGroup
                            );
                        }
                    }
                    if (
                        $attributeOrGroup instanceof ShorthandTypedAttribute
                        || $attributeOrGroup instanceof CompoundTypedAttribute
                    ) {
                        $list->merge($this->getAllAttributesFromShorthandOrCompound($attributeOrGroup));

                        continue;
                    }
                    $list->add($attributeOrGroup);
                }
            }

            return $list;
        });
        assert($result instanceof TypedAttributeCollection);

        return $result;
    }

    /**
     * Utility method to get all attributes inside a ShorthandTypedAttribute or CompoundTypedAttribute.
     *
     * @param  ShorthandTypedAttribute|CompoundTypedAttribute  $attribute  Attribute to look into
     */
    private function getAllAttributesFromShorthandOrCompound(
        ShorthandTypedAttribute|CompoundTypedAttribute $attribute
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ): TypedAttributeCollection {
        $list = new TypedAttributeCollection();
        $list->add($attribute);

        return $list;
    }
}
