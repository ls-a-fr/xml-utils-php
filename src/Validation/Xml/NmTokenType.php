<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * An Nmtoken (name token) is any mixture of name characters.
 * [Definition: A Name is an Nmtoken with a restricted set of initial characters.]
 * Disallowed initial characters for Names include digits, diacritics, the full
 * stop and the hyphen.
 *
 * @see https://www.w3.org/TR/xml/#NT-Nmtoken
 */
class NmTokenType extends Type implements Validator
{
    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator(
            NameCharacterType::NAME_CHAR.'+',
            'u'
        ));
    }
}
