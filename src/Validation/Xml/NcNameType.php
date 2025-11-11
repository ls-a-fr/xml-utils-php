<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * [4]   NCName   ::=   NCNameStartChar NCNameChar*. An XML Name, minus the ":"
 *
 * @see https://www.w3.org/TR/xml-names11/#ns-decl
 */
class NcNameType extends Type implements Validator
{
    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator(
            // Please PHPCS, have some mercy for readability
            // phpcs:disable Generic.Strings.UnnecessaryStringConcat.Found
            // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound
            '('.NameStartCharacterType::UNPREFIXABLE_NAME_START_CHAR.')'.
            '('.NameCharacterType::UNPREFIXABLE_NAME_CHAR.')+',
            'u'
        ));
    }
}
