<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * A NameStartCharacter Type validates a valid first character for a tag or attribute name
 */
class NameStartCharacterType extends Type implements Validator
{
    /**
     * Valid first character for a name
     *
     * @see https://www.w3.org/TR/xml/#NT-NameStartChar
     */
    // phpcs:disable Generic.Files.LineLength.MaxExceeded
    public const NAME_START_CHAR = ":|[A-Z]|_|[a-z]|[\x{C0}-\x{D6}]|[\x{D8}-\x{F6}]|[\x{F8}-\x{02FF}]|[\x{0370}-\x{037D}]|[\x{037F}-\x{1FFF}]|[\x{200C}-\x{200D}]|[\x{2070}-\x{218F}]|[\x{2C00}-\x{2FEF}]|[\x{3001}-\x{D7FF}]|[\x{F900}-\x{FDCF}]|[\x{FDF0}-\x{FFFD}]";

    // phpcs:disable Generic.Files.LineLength.MaxExceeded
    public const UNPREFIXABLE_NAME_START_CHAR = "[A-Z]|_|[a-z]|[\x{C0}-\x{D6}]|[\x{D8}-\x{F6}]|[\x{F8}-\x{02FF}]|[\x{0370}-\x{037D}]|[\x{037F}-\x{1FFF}]|[\x{200C}-\x{200D}]|[\x{2070}-\x{218F}]|[\x{2C00}-\x{2FEF}]|[\x{3001}-\x{D7FF}]|[\x{F900}-\x{FDCF}]|[\x{FDF0}-\x{FFFD}]";

    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator(
            self::NAME_START_CHAR,
            'u'
        ));
    }
}
