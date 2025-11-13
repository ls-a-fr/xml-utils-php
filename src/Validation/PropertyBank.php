<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Collections\TypedAttributeCollection;
use Lsa\Xml\Utils\Contracts\HasPostConstruct;
use Lsa\Xml\Utils\Exceptions\PropertyNotFoundException;
use Lsa\Xml\Utils\Validation\Base\Type;
use ReflectionClass;

/**
 * Stores every registered property for this package, and allows to change profiles between code blocks
 */
class PropertyBank
{
    /**
     * The attribute bank
     *
     * @var array<string, TypedAttribute>
     */
    private static array $bank;

    /**
     * Added attributes after definition, if needed
     *
     * @var array<string, TypedAttribute>
     */
    private static array $virtual;

    /**
     * Definitions for several profiles
     *
     * @var array<string, array<string, TypedAttribute|class-string<TypedAttribute>>>
     */
    private static array $definitions = [];

    /**
     * Source stack, allows to dynamically change Profile
     *
     * @var list<string>
     */
    private static array $sourceStack;

    /**
     * Initializes the PropertyBank
     *
     * @param  string  $name  Profile name, or any name you would like to set definitions to
     * @param  array<string,class-string<TypedAttribute>>  $definitions  Property definitions
     */
    public static function initialize(string $name, array $definitions): void
    {
        self::pushSource($name);

        if (isset($definitions[$name]) === true) {
            return;
        }
        self::$definitions[$name] = $definitions;
    }

    /**
     * Adds a new source in the stack
     *
     * @param  string  $name  Source to get definitions from
     */
    public static function pushSource(string $name): void
    {
        if (self::getCurrentSource() !== $name) {
            self::$sourceStack[] = $name;
        }
    }

    /**
     * Stop using this source, pop it from the stack
     */
    public static function popSource(): void
    {
        array_pop(self::$sourceStack);
    }

    public static function addVirtual(string|TypedAttribute|AttributeGroup|Type $value, string|int|null $key = null)
    {
        // AttributeGroup handling
        if(\is_string($value) === true && \is_subclass_of($value, AttributeGroup::class)) {
            $value = new $value();
        }
        if($value instanceof AttributeGroup) {
            foreach($value->attributeNames as $name) {
                self::addVirtual($name);
            }
            return;
        }

        // Property handling
        $propertyName = null;
        $propertyType = null;

        if(\is_string($key) === true) {
            $propertyName = $key;
            $propertyType = $value;
        } else if($value instanceof TypedAttribute) {
            $propertyName = $value->name;
            $propertyType = $value;
        } else if(\is_subclass_of($value, TypedAttribute::class) === true) {
            /**
             * @var TypedAttribute $property
             */
            $property = new $value();
            $propertyName = $property->name;
            $propertyType = $property->type;
        }
        
        if($propertyName === null || $propertyType === null) {
            throw new PropertyNotFoundException('Cannot find name or type for this property');
        }

        // Add property if necessary
        static::getBank();
        // Do not add if property already exists
        if (isset(self::$bank[$propertyName]) === true) {
            return self::$bank[$propertyName];
        }
        if (isset(self::$virtual[$propertyName]) === true) {
            return self::$virtual[$propertyName];
        }

        self::$virtual[$propertyName] = $propertyType;
    }

    /**
     * Returns one typed attribute based on an attribute name.
     * Throws an exception when not found.
     *
     * @param  string  $propertyName  The attribute name
     * @return \Lsa\Xml\Utils\Validation\TypedAttribute The found typed attribute
     *
     * @throws PropertyNotFoundException
     */
    public static function getOne(string $propertyName): TypedAttribute
    {
        static::getBank();
        if (isset(self::$bank[$propertyName]) === true) {
            return self::$bank[$propertyName];
        }

        if (isset(self::$virtual) === true && isset(self::$virtual[$propertyName]) === true) {
            return self::$virtual[$propertyName];
        }

        if (\str_contains($propertyName, '.') === true) {
            $parts = explode('.', $propertyName);
            if (isset(self::$bank[$parts[0]]) === true) {
                $property = self::$bank[$parts[0]];
                if ($property instanceof CompoundTypedAttribute) {
                    foreach ($property->getAttributes() as $innerAttribute) {
                        if ($innerAttribute->name === $propertyName) {
                            self::$virtual[$propertyName] = $innerAttribute;

                            return self::$virtual[$propertyName];
                        }
                    }
                }
            }
        }
        throw new PropertyNotFoundException('Property not found: '.$propertyName);
    }

    /**
     * Returns one typed attribute based on a class name.
     * Throws an exception when not found.
     *
     * @param  string  $className  The attribute Type class
     * @return \Lsa\Xml\Utils\Validation\TypedAttribute The found typed attribute
     *
     * @throws PropertyNotFoundException
     */
    public static function getByClassName(string $className): TypedAttribute
    {
        static::getBank();
        $definitions = self::getDefinitions();
        $index = \array_search($className, $definitions);

        if ($index !== false) {
            return self::getOne($index);
        }

        $index = \array_search($className, (self::$virtual ?? []));
        if ($index !== false) {
            return self::$virtual[$className];
        }

        if (\class_exists($className) === true && \is_subclass_of($className, TypedAttribute::class) === true) {
            $rc = new ReflectionClass($className);
            $constructor = $rc->getConstructor();
            if ($constructor === null || $constructor->getNumberOfParameters() !== 0) {
                throw new PropertyNotFoundException('Cannot instanciate class as it has parameters: '.$className);
            }
            if (isset(self::$virtual) === false) {
                self::$virtual = [];
            }
            // @phpstan-ignore arguments.count
            self::$virtual[$className] = new $className();

            return self::$virtual[$className];
        }
        throw new PropertyNotFoundException('Property class-name not found: '.$className);
    }

    /**
     * Returns several typed attributes based on their attribute names.
     * Only found attributes are returned.
     *
     * @param  list<string>  $propertyNames  Attribute names
     * @return \Lsa\Xml\Utils\Collections\TypedAttributeCollection The found typed attributes
     */
    public static function get(array $propertyNames): TypedAttributeCollection
    {
        static::getBank();

        return (new TypedAttributeCollection())->addAll(
            \array_values(
                array_filter(self::$bank, fn ($k) => in_array($k, $propertyNames), ARRAY_FILTER_USE_KEY)
            )
        );
    }

    /**
     * Get the property bank.
     *
     * @return array<string, \Lsa\Xml\Utils\Validation\TypedAttribute> The property bank
     *
     * @throws PropertyNotFoundException
     */
    public static function getBank(): array
    {
        if (isset(self::$bank) === false) {
            self::$bank = [];

            $needPostConstruct = [];
            foreach (static::getDefinitions() as $propertyName => $propertyType) {
                if (\is_string($propertyType) === true) {
                    $rc = new ReflectionClass($propertyType);
                    $constructor = $rc->getConstructor();
                    if ($constructor === null || $constructor->getNumberOfParameters() !== 0) {
                        throw new PropertyNotFoundException(
                            'Cannot instanciate class as it has parameters: '.$propertyType
                        );
                    }

                    // @phpstan-ignore arguments.count
                    $property = new $propertyType();
                } else {
                    $property = $propertyType;
                }

                if ($property instanceof HasPostConstruct) {
                    $needPostConstruct[] = $property;
                }
                self::$bank[$propertyName] = $property;
            }
            foreach ($needPostConstruct as $property) {
                $property->postConstruct();
            }
        }

        return self::$bank;
    }

    /**
     * Returns current source, or null if no current source set.
     */
    protected static function getCurrentSource(): ?string
    {
        if (isset(self::$sourceStack) === false) {
            return null;
        }
        if (empty(self::$sourceStack) === true) {
            \trigger_error('Cannot find current source, you may have a misplaced popSource call');

            return null;
        }

        return self::$sourceStack[(count(self::$sourceStack) - 1)];
    }

    /**
     * Get typed attribute definitions.
     * Key is the attribute name, value is the type class string.
     *
     * @return array<string, class-string<TypedAttribute>|TypedAttribute> The bank definitions
     */
    public static function getDefinitions(): array
    {
        $currentSourceStack = self::getCurrentSource();
        if ($currentSourceStack === null) {
            return [];
        }

        return self::$definitions[$currentSourceStack];
    }
}
