<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Validators;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Traits\ProvidesSelfValidation;
use Lsa\Xml\Utils\Validation\Base\Type;

/**
 * A RegexValidator will verify if the regular expression filled in match the supplied
 * value. Note that this validator encloses the regular expression with start and end
 * delimiters, thus the full string must match.
 */
class RegexValidator extends Type implements Validator
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
     * Force XSD validation to base="string" even if no spaces in regex
     */
    public readonly bool $forceAllowSpaces;

    /**
     * Creates a new RegexValidator
     *
     * @param  string  $expression  A regular expression
     * @param  string  $expressionFlags  Flags forwarded to preg_match function
     * @param  bool  $forceAllowSpaces  Force XSD validation to base="string" even if no spaces in regex
     */
    public function __construct(string $expression, string $expressionFlags = '', bool $forceAllowSpaces = false)
    {
        $this->expression = $expression;
        $this->expressionFlags = $expressionFlags;
        $this->forceAllowSpaces = $forceAllowSpaces;
    }

    /**
     * Test the regular expression against the specified value, with start and end delimiter.
     *
     * @return bool `true` if the regular expression match the value, `false` otherwise.
     */
    public function validate(string $value): bool
    {
        return preg_match('/^'.$this->expression.'$/'.$this->expressionFlags, $value) === 1;
    }
}
