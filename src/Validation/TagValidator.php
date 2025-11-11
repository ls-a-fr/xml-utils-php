<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Collections\SchemaElementCollection;
use Lsa\Xml\Utils\Contracts\DataAwareValidator;
use Lsa\Xml\Utils\Contracts\HasDefinition;
use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Node;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * A TagValidator is built as a Factory, even if it's only a Singleton.
 * It allows to make validators and validate types, based on available hooks.
 *
 * When validating a Node, this TagValidator first calls every "Definition Hooks" registered,
 * small functions to modify, if necessary, what is allowed for every tag.
 * Then it performs the validation against the Tag definition.
 */
final class TagValidator
{
    /**
     * Errors thrown while validating
     *
     * @var list<string>
     */
    private array $errors;

    /**
     * Available hooks are gathered from static property `$hooks`
     *
     * @var list<callable(EmptyTag&HasDefinition, Definition):?Definition>
     */
    private array $availableHooks;

    /**
     * Available value computers are gathered from static property `$valueComputers`
     *
     * @var list<callable(EmptyTag, TypedAttribute, ?string):?string>
     */
    private array $valueComputers;

    /**
     * Current instance
     */
    private static ?TagValidator $instance;

    /**
     * Hooks used for validating. Can use a specific Profile (see xsd-generator) or any other tool.
     *
     * @var list<callable(EmptyTag&HasDefinition, Definition):?Definition>
     */
    private static array $hooks = [];

    /**
     * Hooks used for validating. Can use a specific function.
     *
     * @var list<callable(EmptyTag, TypedAttribute, ?string):?string>
     */
    private static array $computers = [];

    /**
     * Make an instance of TagValidator
     */
    public static function make(): static
    {
        if (isset(self::$instance) === false) {
            self::$instance = new self();
            self::$instance->availableHooks = self::$hooks;
            self::$instance->valueComputers = self::$computers;
            self::$hooks = [];
        }

        return self::$instance;
    }

    /**
     * Adds a definition hook. Meaning: transforms the defaults validation based on specific rules,
     * set in a Definition object. May be useful to comply with a specific XSD structure from various
     * XML sources.
     *
     * @param  callable(EmptyTag&HasDefinition, Definition):?Definition  $hook  A definition hook
     */
    public static function addDefinitionHook(callable $hook): void
    {
        if (isset(self::$instance) === true) {
            self::$hooks = self::$instance->availableHooks;
            self::$computers = self::$instance->valueComputers;
            self::$instance = null;
        }
        self::$hooks[] = $hook;
    }

    /**
     * Adds a computer hook. Meaning: transforms any value from any attribute through this function.
     * Allows to evaluate values like function call.
     *
     * @param  callable(EmptyTag $node, TypedAttribute $attribute, ?string $value):?string  $hook  A computer hook
     */
    public static function addValueComputer(callable $hook): void
    {
        if (isset(self::$instance) === true) {
            self::$hooks = self::$instance->availableHooks;
            self::$computers = self::$instance->valueComputers;
            self::$instance = null;
        }
        self::$computers[] = $hook;
    }

    public static function cleanDefinitionHooks(): void
    {
        if (isset(self::$instance) === true) {
            self::$instance = null;
        }
        self::$hooks = [];
    }

    /**
     * Execute registered definition hooks.
     *
     * @param  EmptyTag&HasDefinition  $node  Node to validate
     * @param  Definition  $definition  Current node definition
     * @return ?Definition The tag definition, or null if the tag is forbidden
     */
    private function executeDefinitionHooks(EmptyTag&HasDefinition $node, Definition $definition): ?Definition
    {
        foreach ($this->availableHooks as $hook) {
            $definition = $hook($node, $definition);

            // Definition was set to null, no need to continue
            if ($definition === null) {
                return null;
            }
        }

        return $definition;
    }

    /**
     * Get raised errors from this validation.
     *
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validates a Node.
     *
     * @return bool True if the validation succeeds, false otherwise
     */
    public function validate(EmptyTag&HasDefinition $node): bool
    {
        $definition = $this->executeDefinitionHooks(
            $node,
            $node->asDefinition()
        );
        $this->errors = [];

        $validated = $this->validateAttributes($node, $definition) && $this->validateTags($node, $definition);

        return $validated;
    }

    /**
     * Validate children, recursively
     *
     * @param  EmptyTag&HasDefinition  $node  Node to validate
     * @param  Definition  $definition  Current node definition
     * @return bool True if children complies with the Definition, false otherwise
     */
    protected function validateTags(EmptyTag&HasDefinition $node, ?Definition $definition): bool
    {
        // Definition was obliterated by hooks
        // Probably a forbidden node
        if ($definition === null) {
            $this->errors[] = $node->getTagName().' is not allowed here, its Definition is null';

            return false;
        }
        $schemaElements = $definition->schemaElements;
        foreach ($schemaElements as $element) {
            if ($element instanceof Sequence) {
                if ($this->validateSequence($node, $element) === false) {
                    return false;
                }
            }
            if ($element instanceof Choice) {
                if ($this->validateChoice($element) === false) {
                    return false;
                }
            }
        }
        if (($node instanceof Tag) === false) {
            return true;
        }

        return $this->validateTagChildren($node, $definition);
    }

    /**
     * Validate a specific tag children.
     *
     * @param  Tag&HasDefinition  $node  Node to validate
     * @param  Definition  $definition  Current node definition
     * @return bool True if children complies with the Definition, false otherwise
     */
    protected function validateTagChildren(Tag&HasDefinition $node, ?Definition $definition)
    {
        $children = $node->getChildren();

        foreach ($children as $child) {
            // Skip text nodes
            if (($child instanceof EmptyTag) === false) {
                continue;
            }
            // Skip tags without definition
            if (($child instanceof HasDefinition) === false) {
                continue;
            }
            if ($this->isAllowed($child, $definition) === false) {
                return false;
            }

            if ($this->validate($child) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate the Definition sequence against the Node
     *
     * @param  EmptyTag&HasDefinition  $node  Node to validate
     * @param  Sequence  $sequence  Current node definition
     * @return bool True if node complies with the Definition, false otherwise
     */
    protected function validateSequence(EmptyTag&HasDefinition $node, Sequence $sequence): bool
    {
        foreach ($sequence->elements as $element) {
            if ($element instanceof Choice) {
                if ($this->validateChoice($element) === false) {
                    return false;
                }

                continue;
            }
            /**
             * In a Sequence, you can have Choices, Element, or Any nodes.
             * Thus, it must be an Element or Any object.
             *
             * @var Element|Any $element
             */
            if ($this->validateOccurrences($node, $element) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate elements inside the Definition against the Node
     *
     * @param  EmptyTag&HasDefinition  $node  Node to validate occurences inside
     * @param  Element|Any  $element  Element allowed
     * @return bool True if node complies with the Definition, false otherwise
     */
    protected function validateOccurrences(EmptyTag&HasDefinition $node, Element|Any $element): bool
    {
        if (($node instanceof Tag) === false) {
            return $this->returnedAsNotApplicationValidation();
        }

        if ($element->minOccurs !== null && $element->minOccurs > 0) {
            if ($this->countOccurences($node, $element->ref) < $element->minOccurs) {
                $this->errors[] = $element->ref.' must be at least '.$element->minOccurs.' occurences';

                return false;
            }
        }
        if ($element->maxOccurs !== null && \is_string($element->maxOccurs) === false) {
            // Call to is_string refers to "unbounded" value
            // Can be replaced by "different from unbounded" but fails phpstan validation
            if ($this->countOccurences($node, $element->ref) > ($element->maxOccurs ?? 1)) {
                $this->errors[] = $element->ref.' must be at most '.$element->maxOccurs.' occurences';

                return false;
            }
        }

        return true;
    }

    /**
     * Utility method to check occurences of a specific class reference inside a Tag.
     *
     * @param  Tag  $node  Node to check occurences in
     * @param  ?class-string<EmptyTag>  $refClass  Reference class of Element
     * @return int Number of occurences
     */
    private function countOccurences(Tag $node, ?string $refClass): int
    {
        $found = $node->getChildren()->filter(function ($c) use ($refClass) {
            if ($refClass === null) {
                return false;
            }

            return get_class($c) === $refClass || \is_subclass_of($c, $refClass) === true;
        });

        return $found->count();
    }

    /**
     * Validates a Choice
     *
     * @param  Choice  $choice  The choice to validate
     * @return bool True if node complies with the Definition, false otherwise
     */
    protected function validateChoice(Choice $choice): bool
    {
        if ($choice->elements->isEmpty() === true) {
            $this->errors[] = get_class($choice).' is empty, this is a misconfiguration';

            return true;
        }
        if ($choice->minOccurs !== null && $choice->elements->count() < $choice->minOccurs) {
            $this->errors[] = get_class($choice).' must be at least '.$choice->minOccurs.' occurences';

            return false;
        }
        if ($choice->maxOccurs !== null && $choice->elements->count() > $choice->maxOccurs) {
            $this->errors[] = get_class($choice).' must be at most '.$choice->maxOccurs.' occurences';

            return false;
        }

        return true;
    }

    /**
     * Check if the specified node is allowed in this Definition
     *
     * @param  EmptyTag&HasDefinition  $node  Node to validate
     * @param  ?Definition  $definition  Current node definition
     * @return bool True if node complies with the Definition, false otherwise
     */
    protected function isAllowed(EmptyTag&HasDefinition $node, ?Definition $definition): bool
    {
        $result = $this->isAllowedRecursive($node, $definition?->schemaElements);
        if ($result === false) {
            $this->errors[] = get_class($node).' is not allowed';
        }

        return $result;
    }

    /**
     * Check if the inner nodes are allowed in this Definition
     *
     * @param  EmptyTag&HasDefinition  $node  Node to validate
     * @param  ?SchemaElementCollection  $schemaElements  Allowed nodes
     * @return bool True if node complies with the Definition, false otherwise
     */
    protected function isAllowedRecursive(EmptyTag&HasDefinition $node, ?SchemaElementCollection $schemaElements): bool
    {
        if ($schemaElements === null) {
            return false;
        }
        foreach ($schemaElements as $element) {
            if ($element instanceof Any) {
                return true;
            }
            if ($element instanceof Element) {
                if (
                    get_class($node) === $element->ref
                    || \is_subclass_of($node, $element->ref) === true
                ) {
                    return true;
                }

                continue;
            }
            if ($element instanceof Choice || $element instanceof Sequence) {
                if ($this->isAllowedRecursive($node, $element->elements) === true) {
                    return true;
                }

                continue;
            }

            return false;
        }

        return false;
    }

    /**
     * Check for invalid attribute values in this Node.
     *
     * @param  EmptyTag&HasDefinition  $node  Node to validate
     * @param  ?Definition  $definition  Allowed nodes
     * @return bool True if node complies with the Definition, false otherwise
     */
    protected function validateAttributes(EmptyTag&HasDefinition $node, ?Definition $definition): bool
    {
        if ($definition === null) {
            return $this->returnedAsNotApplicationValidation();
        }
        // Every available attribute on this Definition
        $allowedAttributes = $definition->getRegisteredAttributes();

        foreach ($node->attributes as $attribute) {
            // Skip prefix definitions or xml definitions
            if ($attribute->prefix === 'xmlns' || $attribute->prefix === 'xml') {
                continue;
            }

            // Check if any attribute is available on this Definition with this name
            if (isset($allowedAttributes[$attribute->name]) === false) {
                $this->errors[] = 'Attribute '.$attribute->name.' is disallowed on '.$node->getTagName();

                return false;
            }

            $validatorClassName = $allowedAttributes[$attribute->name];
            $validator = new $validatorClassName();
            $value = $attribute->value;
            $property = PropertyBank::getOne($attribute->name);

            // Use implementation-specific value computing if necessary
            foreach ($this->valueComputers as $computer) {
                $value = strval($computer($node, $property, $value));
            }

            if ($validator instanceof DataAwareValidator) {
                $validationResult = $validator->validateWithContext($attribute->value, $node->root(), $node);
            } else {
                /**
                 * Better safe than sorry
                 *
                 * @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue
                 */
                assert($validator instanceof Validator);
                $validationResult = $validator->validate($attribute->value);
            }

            // Check if the value is valid with the validator
            if ($validationResult === false) {
                $this->errors[] = 'Attribute '.$attribute->name.' has an invalid value: '.$attribute->value;

                return false;
            }
        }

        return true;
    }

    /**
     * Default value returnes as an some not application validation.
     * This is different from an invalid validation (which will return false).
     *
     * This method is used as a "lax" validation system, called whenever it's "okay enough".
     *
     * @return true
     */
    protected function returnedAsNotApplicationValidation(): bool
    {
        return true;
    }
}
