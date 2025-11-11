<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Validators;

use Lsa\Xml\Utils\Contracts\MultiValidator;
use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Traits\HasSeparator;
use Lsa\Xml\Utils\Traits\ProvidesMultipleValidators;
use Lsa\Xml\Utils\Traits\ProvidesSelfValidation;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * A CumulativeOrderedValidator allows to validate several strings set in an attribute value.
 * This validator will split the value based on a separator, then check every chunk with any
 * Validator.
 */
class CumulativeValidator extends Type implements MultiValidator, Validator
{
    use HasSeparator;
    use ProvidesMultipleValidators;
    use ProvidesSelfValidation;

    /**
     * Ordered validators
     *
     * @var list<\Lsa\Xml\Utils\Contracts\Validator>
     */
    public readonly array $validators;

    /**
     * Creates a new CumulativeValidator
     *
     * @param  list<\Lsa\Xml\Utils\Contracts\Validator>  ...$validators  Ordered validators
     */
    public function __construct(Validator ...$validators)
    {
        $this->validators = \array_values($validators);
    }

    /**
     * Splits the string in several chunks, based on the specified separator. Then validate every chunk
     * with any validator. If a validation succeeds, the next value is checked.
     */
    public function validateWithContext(string $value, ?Tag $root, ?EmptyTag $current): bool
    {
        $parts = $this->separate($value);
        $validators = [...$this->validators];
        foreach ($parts as $part) {
            if ($this->shouldTrim === true) {
                $part = trim($part);
            }
            if (empty($validators) === true) {
                return false;
            }
            foreach ($validators as $validatorIndex => $validator) {
                if ($this->validateSingleValidator($validator, $part, $root, $current) === true) {
                    unset($validators[$validatorIndex]);

                    continue 2;
                }
            }

            return false;
        }

        if (empty($validators) === false) {
            return false;
        }

        return true;
    }

    /**
     * Get validators
     *
     * @return list<Validator>
     */
    protected function getValidators(): array
    {
        return $this->validators;
    }
}
