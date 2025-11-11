<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * A BlankCharacter Type validates blank characters.
 * Legal characters are tab, carriage return, line feed, and the
 * legal characters of Unicode and ISO/IEC 10646.
 *
 * @see https://www.w3.org/TR/xml/#NT-Char
 */
class BlankCharacterType extends Type implements Validator
{
    /**
     * Regular expression matching valid characters
     */
    public const VALID_CHARACTER = "\x{20}|\x{9}|\x{D}|\x{A}";

    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator(self::VALID_CHARACTER));
    }
}
