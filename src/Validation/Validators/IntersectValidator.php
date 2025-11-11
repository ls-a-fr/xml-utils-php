<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Validators;

use Lsa\Xml\Utils\Contracts\MultiValidator;
use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Traits\ProvidesMultipleValidators;
use Lsa\Xml\Utils\Traits\ProvidesSelfValidation;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * An IntersectValidator validates a single string with multiple validators. If a single validator
 * fails, the validation process fails.
 */
class IntersectValidator extends Type implements MultiValidator, Validator
{
    use ProvidesMultipleValidators;
    use ProvidesSelfValidation;

    /**
     * Ordered validators
     *
     * @var list<\Lsa\Xml\Utils\Contracts\Validator>
     */
    public readonly array $validators;

    /**
     * Creates a new IntersectValidator
     *
     * @param  list<\Lsa\Xml\Utils\Contracts\Validator>  ...$validators  Ordered validators
     */
    public function __construct(Validator ...$validators)
    {
        $this->validators = \array_values($validators);
    }

    /**
     * Check the value with every validator, one after another.
     */
    public function validateWithContext(string $value, ?Tag $root, ?EmptyTag $current): bool
    {
        foreach ($this->validators as $validator) {
            if ($this->validateSingleValidator($validator, $value, $root, $current) === false) {
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
