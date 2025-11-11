<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * [5]   Name   ::=   NameStartChar (NameChar)*
 *
 * @see https://www.w3.org/TR/REC-xml/#NT-Name
 */
class NameType extends Type implements Validator
{
    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator(
            // Please PHPCS, have some mercy for readability
            // phpcs:disable Generic.Strings.UnnecessaryStringConcat.Found
            // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound
            '('.NameStartCharacterType::NAME_START_CHAR.')'.
            '('.NameCharacterType::NAME_CHAR.')+',
            'u'
        ));
    }
}
