<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Validators;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Traits\ProvidesSelfValidation;
use Lsa\Xml\Utils\Validation\Base\Type;

/**
 * An EnumValidator allows for a pre-determined and fixed set of values.
 */
class EnumValidator extends Type implements Validator
{
    use ProvidesSelfValidation;

    /**
     * Allowed values
     *
     * @var string[]
     */
    public readonly array $values;

    /**
     * Force XSD validation to base="string" even if no spaces in regex
     */
    public readonly bool $forceAllowSpaces;

    /**
     * Creates a new EnumValidator
     *
     * @param  string[]  $values  Allowed values
     * @param  bool  $forceAllowSpaces  Force XSD validation to base="string" even if no spaces in regex
     */
    public function __construct(array $values, bool $forceAllowSpaces = false)
    {
        $this->values = $values;
        $this->forceAllowSpaces = $forceAllowSpaces;
    }

    /**
     * Test the supplied value against allowed values.
     */
    public function validate(string $value): bool
    {
        return in_array($value, $this->values, true);
    }

    /**
     * Ensures this validator is valid. You may have:
     * - empty values
     * - empty string value
     * - duplicates
     *
     * Note that isValid does not necessarily mean you can't use it.
     *
     * @return bool True is this validator is valid, false otherwise.
     */
    public function isValid(): bool
    {
        if (empty($this->values) === false) {
            return false;
        }
        $distinctValues = \array_unique($this->values);
        if (count($distinctValues) !== count($this->values)) {
            return false;
        }
        if (array_search('', $this->values, true) === 0) {
            return false;
        }

        return true;
    }
}
