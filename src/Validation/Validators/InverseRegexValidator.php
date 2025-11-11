<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Validators;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Traits\ProvidesSelfValidation;
use Lsa\Xml\Utils\Validation\Base\Type;

/**
 * An InverseRegexValidator will verify if no forbidden character is in
 * the supplied value.
 */
class InverseRegexValidator extends Type implements Validator
{
    use ProvidesSelfValidation;

    /**
     * A regular expression
     */
    public readonly string $expression;

    /**
     * Flags forwarded to preg_match function
     */
    public readonly string $expressionFlags;

    /**
     * Creates a new InverseRegexValidator
     *
     * @param  string  $expression  A regular expression
     * @param  string  $expressionFlags  Flags forwarded to preg_match function
     */
    public function __construct(string $expression, string $expressionFlags = '')
    {
        $this->expression = $expression;
        $this->expressionFlags = $expressionFlags;
    }

    /**
     * Test the regular expression against the specified value.
     *
     * @return bool `true` if the regular expression does NOT match the value, `false` otherwise.
     */
    public function validate(string $value): bool
    {
        return preg_match('/'.$this->expression.'/'.$this->expressionFlags, $value) !== 1;
    }
}
