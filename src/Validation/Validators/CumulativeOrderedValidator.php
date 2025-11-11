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
 * This validator will split the value based on a separator, then check every chunk with the
 * corresponding index-based Validator.
 */
class CumulativeOrderedValidator extends Type implements MultiValidator, Validator
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
     * Creates a new CumulativeOrderedValidator
     *
     * @param  list<\Lsa\Xml\Utils\Contracts\Validator>  ...$validators  Ordered validators
     */
    public function __construct(Validator ...$validators)
    {
        $this->validators = \array_values($validators);
    }

    /**
     * Splits the string in several chunks, based on the specified separator.
     * Then validate every chunk with the corresponding validator.
     */
    public function validateWithContext(string $value, ?Tag $root, ?EmptyTag $current): bool
    {
        $parts = $this->separate($value);
        foreach ($parts as $index => $part) {
            if ($this->shouldTrim === true) {
                $part = trim($part);
            }
            if ($this->validateSingleValidator($this->validators[$index], $part, $root, $current) === false) {
                return false;
            }
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
