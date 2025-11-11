<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * A NameCharacter Type validates valid characters (except first) for a tag or attribute name
 */
class NameCharacterType extends Type implements Validator
{
    /**
     * Valid other characters for a name
     *
     * @see https://www.w3.org/TR/xml/#NT-NameChar
     */
    // phpcs:disable Generic.Files.LineLength.TooLong
    public const NAME_CHAR = NameStartCharacterType::NAME_START_CHAR."|\-|\.|[0-9]|\x{B7}|[\x{0300}-\x{036F}]|[\x{203F}-\x{2040}]";

    // phpcs:disable Generic.Files.LineLength.MaxExceeded
    public const UNPREFIXABLE_NAME_CHAR = NameStartCharacterType::UNPREFIXABLE_NAME_START_CHAR."|\-|\.|[0-9]|\x{B7}|[\x{0300}-\x{036F}]|[\x{203F}-\x{2040}]";

    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator(
            self::NAME_CHAR,
            'u'
        ));
    }
}
