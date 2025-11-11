<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\IntersectValidator;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * A character is an atomic unit of text as specified by ISO/IEC 10646 [ISO/IEC 10646].
 * Legal characters are tab, carriage return, line feed, and the legal characters of
 * Unicode and ISO/IEC 10646.
 *
 * @see https://www.w3.org/TR/xml/#char32
 */
class UnicodeCharacterType extends Type implements Validator
{
    public const CHAR = "(?:[\x{1}-\x{D7FF}]|[#\x{E000}-\x{FFFD}]|[\x{10000}-\x{10FFFF}])";

    public function getValidator(): Validator
    {
        return $this->cache(new IntersectValidator(
            new RegexValidator(
                self::CHAR,
                'u'
            ),
            (new UnicodeRestrictedCharacterType())->getValidator()
        ));
    }
}
