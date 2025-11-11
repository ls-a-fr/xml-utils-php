<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml;

use Lsa\Xml\Utils\Exceptions\InvalidNameException;
use Lsa\Xml\Utils\Traits\ProvidesPrefixDetection;

/**
 * Represents an XML attribute in a tag
 */
class Attribute
{
    use ProvidesPrefixDetection;

    /**
     * Name of the attribute
     */
    public readonly string $name;

    /**
     * LocalName of the attribute, without the prefix
     */
    public readonly string $localName;

    /**
     * Value of the attribute
     */
    public readonly string $value;

    /**
     * Prefix of the attribute, if any
     */
    public readonly string $prefix;

    /**
     * Creates a new Attribute
     *
     * @param  string  $name  Name of this Attribute
     * @param  string  $value  Value or this Attribute
     * @param  string  $prefix  Prefix of this Attribute. Defaults to empty string
     *
     * @throws InvalidNameException If supplied name is invalid
     */
    public function __construct(
        string $name,
        string $value,
        string $prefix = ''
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ) {
        if ($prefix !== '') {
            $name = $prefix.':'.$name;
        }
        if (self::isValidName($name) === false) {
            throw new InvalidNameException('Invalid attribute name: '.$name);
        }
        [$prefix, $localName] = self::splitPrefixAndName($name);
        $this->name = $name;
        $this->localName = $localName;
        $this->prefix = $prefix;
        $this->value = $value;
    }

    /**
     * Creates an attribute from a name an a value. Value can be an array, in this case
     * joining values with a space character
     *
     * @param  string  $name  The attribute name
     * @param  string|list<string>  $value  The value
     * @return \Lsa\Xml\Utils\Xml\Attribute The attribute
     */
    public static function from(string $name, string|array $value): Attribute
    {
        if (is_array($value) === true) {
            $value = implode(' ', $value);
        }

        return new Attribute($name, $value);
    }
}
