<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Collections\AttributeGroupCollection;
use Lsa\Xml\Utils\Collections\SchemaElementCollection;
use Lsa\Xml\Utils\Collections\TypedAttributeCollection;
use Lsa\Xml\Utils\Exceptions\PropertyNotFoundException;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * A Definition is a tag definition, meaning:
 * - Which attributes are allowed
 * - Which attribute groups are allowed
 * - Which tags are allowed (by using sequence and/or choice)
 *
 * Heavily inspired from XSD structure.
 *
 * @phpstan-type UnpackedAttributes array<string,class-string<Type>|Type>
 */
class Definition
{
    /**
     * Every attribute groups for this tag
     */
    public readonly AttributeGroupCollection $referencedAttributeGroups;

    /**
     * Every schema elements allowed for this tag
     */
    public readonly SchemaElementCollection $schemaElements;

    /**
     * Optional. Specifies whether character data is allowed
     * to appear between the child elements of this complexType
     * element. Default is false. If a simpleContent element is
     * a child element, the mixed attribute is not allowed!
     */
    public bool $mixed = false;

    /**
     * Optional. Specifies whether any arbitrary attribute can be added
     * to this Definition.
     */
    public bool $anyAttribute = false;

    /**
     * Inner definition elements
     *
     * @var array{'mandatory':UnpackedAttributes,'inheritable':UnpackedAttributes,'allowed':UnpackedAttributes,'denied':UnpackedAttributes}
     */
    private array $elements = [
        'mandatory' => [],
        'inheritable' => [],
        'allowed' => [],
        'denied' => [],
    ];

    public function __construct()
    {
        $this->referencedAttributeGroups = new AttributeGroupCollection();
        $this->schemaElements = new SchemaElementCollection();
    }

    /**
     * Marks this definition as mixed.
     *
     * @see https://www.w3schools.com/xml/el_complextype.asp
     */
    public function mixed(): static
    {
        $this->mixed = true;

        return $this;
    }

    /**
     * Declares a new sequence in this tag
     *
     * @param  \Lsa\Xml\Utils\Validation\Sequence  $sequence  The sequence
     */
    public function sequence(Sequence $sequence): static
    {
        $this->schemaElements->add($sequence);

        return $this;
    }

    /**
     * Declares a new choice in this tag
     *
     * @param  \Lsa\Xml\Utils\Validation\Choice  $choice  The choice
     */
    public function choice(Choice $choice): static
    {
        $this->schemaElements->add($choice);

        return $this;
    }

    /**
     * Push an definition element to a specified section
     *
     * @param  'mandatory'|'inheritable'|'allowed'|'denied'  $section  Section to push in
     * @param  TypedAttributeCollection|AttributeGroupCollection|UnpackedAttributes|list<TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>>  $data  Data to push in this section
     */
    private function pushToSection(string $section, TypedAttributeCollection|AttributeGroupCollection|array $data): void
    {
        $this->elements[$section] = array_merge($this->elements[$section], $this->normalize($data));
    }

    /**
     * Normalize value to push in a section. As its name suggest, this method can take a various number of types
     *
     * @param  TypedAttributeCollection|AttributeGroupCollection|TypedAttribute|AttributeGroup|array<string,class-string<Type>|Type>|list<TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>>|string  $stuff  Value to normalize
     * @return UnpackedAttributes
     *
     * phpcs:disable Generic.Files.LineLength.TooLong
     */
    private function normalize(TypedAttributeCollection|AttributeGroupCollection|TypedAttribute|AttributeGroup|array|string $stuff): array
    {
        if ($stuff instanceof TypedAttribute) {
            return $stuff->unpack();
        }

        if ($stuff instanceof AttributeGroup) {
            return $this->normalizeAttributeGroup($stuff);
        }

        if (\is_array($stuff) === true && \array_is_list($stuff) === false) {
            // Check array keys are string
            assert(\array_reduce(array_keys($stuff), fn ($acc, $k) => $acc && \is_string($k), true) === true);

            /**
             * Element in a string-based array
             *
             * @var array<string,class-string<Type>|Type> $stuff
             */
            return $stuff;
        }

        // Note than array_is_list check is done before.
        if (
            $stuff instanceof TypedAttributeCollection
            || $stuff instanceof AttributeGroupCollection
            || \is_array($stuff) === true
        ) {
            $result = [];
            /**
             * Element is a collection or a list
             *
             * @var TypedAttributeCollection|AttributeGroupCollection|list<TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>> $stuff
             */
            foreach ($stuff as $thing) {
                $result = array_merge($result, $this->normalize($thing));
            }

            return $result;
        }

        /**
         * Not sure to have full trust on PHPStan annotations
         *
         * @phpstan-ignore function.alreadyNarrowedType, identical.alwaysTrue
         */
        if (\is_string($stuff) === true) {
            // Is it an AttributeGroup subclass?
            if (\is_subclass_of($stuff, AttributeGroup::class) === true) {
                return $this->normalize(new $stuff());
            }
            // Is it a TypedAttribute subclass?
            if (\is_subclass_of($stuff, TypedAttribute::class) === true) {
                $attribute = PropertyBank::getByClassName($stuff);

                return $this->normalize($attribute);
            }
            // Is it a Property name?
            try {
                $property = PropertyBank::getOne($stuff);

                return $this->normalize($property);
                // No need to have a catch body, trigger_error is called right after
                // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            } catch (PropertyNotFoundException) {
                // No.
            }
        }
        \trigger_error('Could not detect element inside: '.$stuff);

        return [];
    }

    /**
     * Utility method to normalize an attribute group
     *
     * @param  AttributeGroup  $attributeGroup  An attribute group
     * @return UnpackedAttributes
     */
    private function normalizeAttributeGroup(AttributeGroup $attributeGroup): array
    {
        // Keep track of groups
        $this->referencedAttributeGroups->add($attributeGroup);

        $attributes = $attributeGroup->asCollection();
        $result = [];
        foreach ($attributes as $attribute) {
            $result = array_merge($result, $attribute->unpack());
        }

        return $result;
    }

    /**
     * Declares several typed attribute for this tag
     *
     * @param  TypedAttributeCollection|AttributeGroupCollection|list<TypedAttribute|AttributeGroup>|array<string,class-string<TypedAttribute|AttributeGroup>>  $allowed  The attributes
     */
    public function allows(TypedAttributeCollection|AttributeGroupCollection|array $allowed): static
    {
        foreach($allowed as $key => $allow) {
            PropertyBank::addVirtual($allow, $key);
        }
        $this->pushToSection('allowed', $allowed);

        return $this;
    }

    /**
     * Declares a new typed attribute for this tag
     *
     * @param  TypedAttribute|AttributeGroup|class-string<TypedAttribute|AttributeGroup>  $allowed  The attribute, or its class name
     */
    public function allow(TypedAttribute|AttributeGroup|string $allowed): static
    {
        PropertyBank::addVirtual($allowed);
        $this->pushToSection('allowed', [$allowed]);

        return $this;
    }

    /**
     * Declares a new inheritable attribute for this tag
     *
     * @param  TypedAttribute|class-string<TypedAttribute>  $inheritable  The attribute, or its class name
     */
    public function inheritable(TypedAttribute|string $inheritable): static
    {
        $this->pushToSection('inheritable', [$inheritable]);

        return $this;
    }

    /**
     * Declares several new inheritable attributes for this tag
     *
     * @param  TypedAttributeCollection|AttributeGroupCollection|list<class-string<TypedAttribute|AttributeGroup>>  $inheritable  The attributes
     */
    public function inheritables(TypedAttributeCollection|AttributeGroupCollection|array $inheritable): static
    {
        $this->pushToSection('inheritable', $inheritable);

        return $this;
    }

    /**
     * Declares several mandatory typed attributes for this tag
     *
     * @param  TypedAttributeCollection|list<class-string<TypedAttribute>>  $mandatory  The attributes
     */
    public function requires(TypedAttributeCollection|array $mandatory): static
    {
        $this->pushToSection('mandatory', $mandatory);

        return $this;
    }

    /**
     * Declares a new mandatory typed attribute applicable for this tag
     *
     * @param  TypedAttribute|class-string<TypedAttribute>  $mandatory  The attribute, or its class
     */
    public function require(TypedAttribute|string $mandatory): static
    {
        $this->pushToSection('mandatory', [$mandatory]);

        return $this;
    }

    /**
     * Get registered attributes in this Definition
     *
     * @return UnpackedAttributes
     */
    public function getRegisteredAttributes(): array
    {
        return $this->unique('allowed', 'mandatory', 'inheritable');
    }

    /**
     * Get unique attributes in specific section(s).
     *
     * @return UnpackedAttributes
     */
    private function unique(string ...$sectionKeys): array
    {
        $result = [];
        foreach ($sectionKeys as $sectionKey) {
            foreach ($this->elements[$sectionKey] as $prop => $value) {
                if (array_key_exists($prop, $this->elements['denied']) === true) {
                    continue;
                }
                if (array_key_exists($prop, $result) === true) {
                    continue;
                }
                $result[$prop] = $value;
            }
        }

        return $result;
    }

    /**
     * Get applied attributes in this Definition
     *
     * @return UnpackedAttributes
     */
    public function getAppliedAttributes(): array
    {
        return $this->unique('allowed', 'mandatory');
    }

    /**
     * Get allowed attributes in this Definition
     *
     * @return UnpackedAttributes
     */
    public function getAllowedAttributes(): array
    {
        return $this->unique('allowed');
    }

    /**
     * Get mandatory attributes in this Definition
     *
     * @return UnpackedAttributes
     */
    public function getMandatoryAttributes(): array
    {
        return $this->unique('mandatory');
    }

    /**
     * Allows any attribute for this tag
     */
    public function allowAny(): static
    {
        $this->anyAttribute = true;

        return $this;
    }

    /**
     * Disallows any attribute for this tag
     */
    public function disallowAny(): static
    {
        $this->anyAttribute = false;

        return $this;
    }

    /**
     * Removes a typed attribute for this tag
     *
     * @param  string|class-string<TypedAttribute|AttributeGroup>|TypedAttribute|AttributeGroup  $deny  Element to deny
     */
    public function deny(string|TypedAttribute|AttributeGroup $deny): static
    {
        if ($deny instanceof AttributeGroup) {
            $this->denyGroupFromClassName(\get_class($deny));
        } elseif (\is_string($deny) === true) {
            if (\is_subclass_of($deny, AttributeGroup::class) === true) {
                $this->denyGroupFromClassName($deny);
            } elseif (\class_exists($deny) === true) {
                $deny = PropertyBank::getByClassName($deny);
            } else {
                $deny = PropertyBank::getOne($deny);
            }
        }
        $this->pushToSection('denied', [$deny]);

        return $this;
    }

    /**
     * Replaces a specific Type in a TypedAttribute for this Definition
     *
     * @param  string  $propertyName  Property name
     * @param  class-string<Type>  $type  Type to define on this property
     */
    public function replaceType(string $propertyName, string $type): static
    {
        $index = $this->findIndexForReplace($propertyName);
        if ($index !== null) {
            unset($this->elements['allowed'][$index]);
        }
        // We still need to add it
        $this->allow(new TypedAttribute($propertyName, $type));

        return $this;
    }

    /**
     * Replaces a TypedAttribute on a Definition
     *
     * @param  string  $propertyName  Property name
     * @param  TypedAttribute  $attribute  Attribute to set
     */
    public function replaceTypedAttribute(string $propertyName, TypedAttribute $attribute): static
    {
        $index = $this->findIndexForReplace($propertyName);
        if ($index !== null) {
            unset($this->elements['allowed'][$index]);
        }
        // We still need to add it
        $this->allow($attribute);

        return $this;
    }

    /**
     * Find index of a specified property name, in an intent to replace it.
     *
     * @param  string  $propertyName  Property name
     */
    protected function findIndexForReplace(string $propertyName): string|int|null
    {
        $index = \array_search($propertyName, $this->elements['allowed']);
        if ($index !== false) {
            return $index;
        }

        // Maybe this property name is stored as a property class name
        try {
            if (\is_subclass_of($propertyName, TypedAttribute::class) === true) {
                $property = PropertyBank::getByClassName($propertyName);
                $searchFor = $property->name;
            } else {
                $property = PropertyBank::getOne($propertyName);
                $searchFor = \get_class($property);
            }
            $index = \array_search($searchFor, $this->elements['allowed']);
            if ($index !== false) {
                return $index;
            }
        } catch (PropertyNotFoundException) {
            // No.
        }

        // Did not find the property
        // This warning could be used with a DEBUG level, something like this:
        // 'Property name: $propertyName was not found on $tagName'
        return null;
    }

    /**
     * Gets the referenced tag name for this Definition.
     * This method is only used for exception messages, and should not be used for anything beyond this purpose.
     */
    protected function getTagNameFromBacktrace(): ?string
    {
        $backtrace = \debug_backtrace();
        foreach ($backtrace as $call) {
            if (isset($call['object']) === true && $call['object'] instanceof Tag) {
                return $call['object']->getTagName();
            }
        }

        return null;
    }

    /**
     * Removes several typed attribute for this tag
     *
     * @param  string  ...$attributeNames  The attribute name properties
     */
    public function denies(string ...$attributeNames): static
    {
        $properties = [];
        foreach ($attributeNames as $attributeName) {
            if (\class_exists($attributeName) === true) {
                $properties[] = PropertyBank::getByClassName($attributeName);
            } else {
                $properties[] = PropertyBank::getOne($attributeName);
            }
        }
        $this->pushToSection('denied', $properties);

        return $this;
    }

    /**
     * Removes a new attribute group for this tag
     *
     * @param  class-string<AttributeGroup>  $className  The attribute group class name
     */
    protected function denyGroupFromClassName(string $className): static
    {
        $group = $this->referencedAttributeGroups->get($className);
        if ($group !== null) {
            $this->referencedAttributeGroups->remove($group);
        }

        return $this;
    }

    /**
     * Moves a Node up to the Sequence/Choice tree.
     *
     * @param  class-string<EmptyTag|Element>  $className  Element to move main
     * @param  ?int  $minOccurs  Minimum occurences for this node
     * @param  string|int|null  $maxOccurs  Maximum occurences for this node
     */
    public function moveMain(string $className, ?int $minOccurs = -1, string|int|null $maxOccurs = -1): static
    {
        [$found, $chain] = $this->deepSearch($this->schemaElements, $className, [$this]);
        if ($found !== null) {
            foreach ($this->schemaElements as $element) {
                if ($element instanceof Sequence || $element instanceof Choice) {
                    // Add minOccurs or maxOccurs if specified
                    if ($minOccurs !== -1 || $maxOccurs !== -1) {
                        if ($minOccurs !== -1) {
                            $found->minOccurs = $minOccurs;
                        }
                        if ($maxOccurs !== -1) {
                            $found->maxOccurs = $maxOccurs;
                        }
                    }
                    $element->elements->add($found);
                    break;
                }
            }

            // Clean the chain
            while (empty($chain) === false) {
                /**
                 * Emptiness of this $chain was validated right before.
                 * Thus, array_pop cannot return null
                 *
                 * @var Definition|Sequence|Choice $parent
                 */
                $parent = array_pop($chain);
                if ($parent instanceof Definition) {
                    continue;
                }
                $parent->elements->remove($found);
                if ($parent->elements->isEmpty() === false) {
                    $found = $parent;
                } else {
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Adds an element to main Sequence/Choice.
     *
     * @param  list<SchemaElement>|SchemaElementCollection|SchemaElement  $elements  Elements to add
     */
    public function addToMain(array|SchemaElementCollection|SchemaElement $elements): static
    {
        if ($elements instanceof SchemaElement) {
            $elements = (new SchemaElementCollection())->add($elements);
        }
        if (\is_array($elements) === true) {
            $elements = (new SchemaElementCollection())->addAll($elements);
        }

        $complex = $this->schemaElements->first();
        if ($complex instanceof Choice || $complex instanceof Sequence) {
            $complex->elements->addAll($elements);
        }

        return $this;
    }

    /**
     * Removes any AttributeGroup on this definition.
     */
    public function removeGroups(): static
    {
        // Gather all attributes referenced by every group
        $unpacked = [];
        foreach ($this->referencedAttributeGroups as $group) {
            $attributes = $group->asCollection();
            foreach ($attributes as $attribute) {
                $unpacked = array_merge($unpacked, $attribute->unpack());
            }
        }

        // Remove them. Note: array_diff work with VALUES, so it would remove any property
        // that shares the same validation type. Definitely not what we wish for.
        foreach (array_keys($this->elements) as $section) {
            foreach (array_keys($unpacked) as $propertyName) {
                if (isset($this->elements[$section][$propertyName]) === true) {
                    unset($this->elements[$section][$propertyName]);
                }
            }
        }
        // Clean the list
        $this->referencedAttributeGroups->clean();

        return $this;
    }

    /**
     * Gathers all elements recursively on a Tag group.
     *
     * @param  SchemaElement  $group  First call must be a Choice, then drills
     */
    private function gatherAllElementsInTagGroup(SchemaElement $group): SchemaElementCollection
    {
        $elements = new SchemaElementCollection();
        if ($group instanceof Choice || $group instanceof Sequence) {
            foreach ($group->elements as $e) {
                $elements->addAll($this->gatherAllElementsInTagGroup($e));
            }

            return $elements;
        }
        $elements->add($group);

        return $elements;
    }

    /**
     * Merges a TagGroup on this definition.
     *
     * @param  Choice  $group  The tag group
     */
    public function mergeTagGroup(Choice $group): static
    {
        $schemaElements = $this->gatherAllElementsInTagGroup($group);

        if ($schemaElements->isEmpty() === false) {
            foreach ($schemaElements as $schemaElement) {
                if (($schemaElement instanceof Element) === false) {
                    continue;
                }
                [$found, $chain] = $this->deepSearch($this->schemaElements, $schemaElement->ref, [$this]);
                if ($found !== null) {
                    while (empty($chain) === false) {
                        /**
                         * Emptiness of this $chain was validated right before.
                         * Thus, array_pop cannot return null
                         *
                         * @var Definition|Sequence|Choice $parent
                         */
                        $parent = array_pop($chain);

                        // Went back to the current definition, last element of $chain.
                        if ($parent instanceof Definition) {
                            $this->schemaElements->remove($found);
                            break;
                        }

                        $parent->elements->remove($found);
                        if ($parent->elements->isEmpty() === false) {
                            break;
                        }
                        $found = $parent;
                    }
                }
            }
        }

        return $this->addToMain($group);
    }

    /**
     * Deny these elements from the current definition.
     *
     * @param  list<class-string<EmptyTag|Element>>  $classNames  Elements to deny
     */
    public function denyElements(array $classNames): static
    {
        foreach ($classNames as $className) {
            $this->denyElement($className);
        }

        return $this;
    }

    /**
     * Deny this element from the current definition.
     *
     * @param  class-string<EmptyTag|Element|SchemaElement>  $className  Element to deny
     */
    public function denyElement(string $className): static
    {
        $found = $this->schemaElements->get($className)->first();
        if ($found !== null) {
            $this->schemaElements->remove($found);

            return $this;
        }

        [$found, $chain] = $this->deepSearch($this->schemaElements, $className, [$this]);
        if ($found !== null) {
            while (empty($chain) === false) {
                /**
                 * Emptiness of this $chain was validated right before.
                 * Thus, array_pop cannot return null
                 *
                 * @var Definition|Sequence|Choice $parent
                 */
                $parent = array_pop($chain);

                // Went back to the current definition, last element of $chain.
                if ($parent instanceof Definition) {
                    $this->schemaElements->remove($found);
                    break;
                }

                $parent->elements->remove($found);
                if ($parent->elements->isEmpty() === false) {
                    break;
                }
                $found = $parent;
            }
        }

        return $this;
    }

    /**
     * Searches recursively in the current Definition.
     *
     * @param  SchemaElementCollection  $elements  Elements to search in
     * @param  class-string<EmptyTag|Element|SchemaElement>  $className  Class name to search for
     * @param  list<Definition|Sequence|Choice>  $chain  Current search tree
     * @return array{0:?SchemaElement,1:null|list<Definition|Sequence|Choice>} First is found element, second is chain
     */
    protected function deepSearch(SchemaElementCollection $elements, string $className, array $chain): array
    {
        // Deep search
        foreach ($elements as $element) {
            if ($element instanceof Choice || $element instanceof Sequence) {
                [$found, $innerChain] = $this->deepSearch($element->elements, $className, [...$chain, $element]);
                if ($found !== null) {
                    return [$found, $innerChain];
                }
            }
            if ($element instanceof Element && $element->ref === $className) {
                return [$element, $chain];
            }
            if ($element instanceof $className) {
                return [$element, $chain];
            }
        }

        return [null, null];
    }
}
