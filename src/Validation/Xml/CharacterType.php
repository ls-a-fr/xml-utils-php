<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\UnionValidator;

/**
 * A single Unicode character valid in accordance with production [2] of [XML] or [XML 1.1].
 * For example, "c" or "&#x2202;".
 *
 * @link http://www.w3.org/TR/REC-xml/
 * @link https://www.w3.org/TR/xml11/
 */
class CharacterType extends Type implements Validator
{
    public function getValidator(): Validator
    {
        return $this->cache(new UnionValidator(
            new CharacterEntityType(),
            new UnicodeCharacterType()
        ));
    }
}
