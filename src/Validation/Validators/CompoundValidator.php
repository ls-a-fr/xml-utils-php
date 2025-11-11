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
 * A CompoundValidator allows to validate several strings set in an attribute value.
 * One must specify minimum and maximum occurences of strings inside, plus a Validator
 * to check on every one of them.
 */
class CompoundValidator extends Type implements MultiValidator, Validator
{
    use HasSeparator;
    use ProvidesMultipleValidators;
    use ProvidesSelfValidation;

    /**
     * Validator object used
     */
    public readonly Validator $validator;

    /**
     * Minimum occurences
     */
    public readonly int $min;

    /**
     * Maximum occurences
     */
    public readonly ?int $max;

    /**
     * Creates a new CompoundValidator
     *
     * @param  \Lsa\Xml\Utils\Contracts\Validator  $validator  Validator object used
     * @param  int  $min  Minimum occurences
     * @param  int  $max  Maximum occurences
     */
    public function __construct(Validator $validator, int $min, ?int $max = null)
    {
        $this->validator = $validator;
        $this->min = $min;
        $this->max = $max;
        $this->validator->setParentValidator($this);
    }

    /**
     * Splits the string in several chunks, based on the specified separator.
     * Then validate every chunk with the specified validator.
     */
    public function validateWithContext(string $value, ?Tag $root, ?EmptyTag $current): bool
    {
        $parts = $this->separate($value);
        if (count($parts) < $this->min || ($this->max !== null && count($parts) > $this->max)) {
            return false;
        }
        foreach ($parts as $part) {
            if ($this->shouldTrim === true) {
                $part = trim($part);
            }
            if ($this->validateSingleValidator($this->validator, $part, $root, $current) === false) {
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
        return [$this->validator];
    }
}
