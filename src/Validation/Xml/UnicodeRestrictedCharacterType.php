<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\InverseRegexValidator;

/**
 * A character is an atomic unit of text as specified by ISO/IEC 10646 [ISO/IEC 10646].
 * Legal characters are tab, carriage return, line feed, and the legal characters of
 * Unicode and ISO/IEC 10646.
 *
 * @see https://www.w3.org/TR/xml11/#char32
 */
class UnicodeRestrictedCharacterType extends Type implements Validator
{
    public const RESTRICTED_CHARS = "[\x{1}-\x{8}]|[\x{B}-\x{C}]|[\x{E}-\x{1F}]|[\x{7F}-\x{84}]|[\x{86}-\x{9F}]";

    /**
     * Document authors are encouraged to avoid "compatibility characters", as defined in
     * Unicode [Unicode]. The characters defined in the following ranges are also discouraged.
     * They are either control characters or permanently undefined Unicode characters:
     */
    // phpcs:disable Generic.Files.LineLength.MaxExceeded
    public const EXTENDED_RESTRICTED_CHARS = "[\x{FDD0}-\x{FDDF}]|[\x{1FFFE}-\x{1FFFF}]|[\x{2FFFE}-\x{2FFFF}]|[\x{3FFFE}-\x{3FFFF}]|[\x{4FFFE}-\x{4FFFF}]|[\x{5FFFE}-\x{5FFFF}]|[\x{6FFFE}-\x{6FFFF}]|[\x{7FFFE}-\x{7FFFF}]|[\x{8FFFE}-\x{8FFFF}]|[\x{9FFFE}-\x{9FFFF}]|[\x{AFFFE}-\x{AFFFF}]|[\x{BFFFE}-\x{BFFFF}]|[\x{CFFFE}-\x{CFFFF}]|[\x{DFFFE}-\x{DFFFF}]|[\x{EFFFE}-\x{EFFFF}]|[\x{FFFFE}-\x{FFFFF}]|[\x{10FFFE}-\x{10FFFF}]";

    public function getValidator(): Validator
    {
        return $this->cache(new InverseRegexValidator(
            self::RESTRICTED_CHARS.'|'.self::EXTENDED_RESTRICTED_CHARS,
            'u'
        ));
    }
}
