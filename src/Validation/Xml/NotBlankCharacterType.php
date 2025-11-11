<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * This Type allows to check if a Character is NOT blank.
 */
class NotBlankCharacterType extends Type implements Validator
{
    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator('(?!'.BlankCharacterType::VALID_CHARACTER.').', 'u'));
    }
}
