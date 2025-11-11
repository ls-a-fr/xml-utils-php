<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

use Lsa\Xml\Utils\Exceptions\InvalidNameException;
use Lsa\Xml\Utils\Exceptions\InvalidPrefixException;
use Lsa\Xml\Utils\Validation\Xml\NameType;

/**
 * Provides several methods associated with XML names and prefixes
 */
trait ProvidesPrefixDetection
{
    /**
     * Checks if the supplied name is prefixed
     *
     * @return bool Returns `true` if supplied name is prefixed, `false` otherwise
     */
    public static function isNamespaced(string $name): bool
    {
        return str_contains($name, ':') && count(explode(':', $name)) === 2;
    }

    /**
     * Splits a name in its prefix and local name. If no prefix is given, the name is left untouched.
     *
     * @return array{0:string,1:string} First index is the prefix, Second is the local name
     *
     * @throws \Lsa\Xml\Utils\Exceptions\InvalidPrefixException
     * @throws \Lsa\Xml\Utils\Exceptions\InvalidNameException
     */
    public static function splitPrefixAndName(string $name): array
    {
        $prefix = '';
        if (self::isNamespaced($name) === true) {
            $parts = explode(':', $name);
            $prefix = $parts[0];
            if (self::isValidName($prefix) === false) {
                throw new InvalidPrefixException();
            }

            $name = $parts[1];
        }

        if (self::isValidName($name) === false) {
            throw new InvalidNameException();
        }

        return [$prefix, $name];
    }

    /**
     * Checks if an XML tag name is valid
     *
     * @param  string  $name  The name
     * @return bool `true` if the name is valid, `false` otherwise
     */
    public static function isValidName(string $name): bool
    {
        return (new NameType())->validate($name);
    }
}
