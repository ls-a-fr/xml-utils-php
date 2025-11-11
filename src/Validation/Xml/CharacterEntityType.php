<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * [Definition: A character reference refers to a specific character in the ISO/IEC 10646 character set, for example one not directly accessible from available input devices.]
 * Character Reference
 * CharRef    ::=    '&#' [0-9]+ ';' | '&#x' [0-9a-fA-F]+ ';' [WFC: Legal Character]
 * Well-formedness constraint: Legal Character
 * Characters referred to using character references MUST match the production for Char.
 * If the character reference begins with "&#x", the digits and letters up to the terminating ;
 * provide a hexadecimal representation of the character's code point in ISO/IEC 10646. If it
 * begins just with "&#", the digits up to the terminating ; provide a decimal representation
 * of the character's code point.
 *
 * @see https://www.w3.org/TR/xml11/#wf-Legalchar
 */
class CharacterEntityType extends Type implements Validator
{
    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator('&#(x[0-9A-Fa-f]+|\d+);'));
    }
}
