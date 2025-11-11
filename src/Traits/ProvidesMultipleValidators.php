<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

use Exception;
use Lsa\Xml\Utils\Contracts\DataAwareValidator;
use Lsa\Xml\Utils\Contracts\MultiValidator;
use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Tag;

trait ProvidesMultipleValidators
{
    /**
     * Gets validators
     *
     * @return list<Validator>
     */
    abstract protected function getValidators(): array;

    /**
     * Validates a value based on its context
     *
     * @param  string  $value  Value to be validated
     * @param  ?Tag  $root  Root tag, if necessary
     * @param  ?EmptyTag  $current  Current tag, if necessary
     * @return bool True if value validates this constraint, false otherwise.
     */
    abstract public function validateWithContext(string $value, ?Tag $root, ?EmptyTag $current): bool;

    /**
     * Validates a value without any context.
     *
     * @return bool True if the validation succeeds, false otherwise.
     */
    public function validate(string $value): bool
    {
        return $this->validateWithContext($value, null, null);
    }

    /**
     * Checks if inner validators needs any context to work.
     *
     * @return bool True if any validator needs a context, false otherwise.
     */
    public function needsContext(): bool
    {
        foreach ($this->getValidators() as $validator) {
            if ($this->shouldForwardContext($validator) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if, for a specified Validator, context should be forward to it.
     *
     * @return bool True is the specified Validator needs a context, false otherwise.
     */
    protected function shouldForwardContext(Validator $validator): bool
    {
        if (in_array(MultiValidator::class, \class_implements($validator)) === true) {
            assert($validator instanceof MultiValidator);
            if ($validator->needsContext() === true) {
                return true;
            }
        }
        if (in_array(DataAwareValidator::class, \class_implements($validator)) === true) {
            return true;
        }

        return false;
    }

    /**
     * Validates a single validator inside this collection of Validators.
     *
     * @param  Validator  $validator  The specified Validator instance
     * @param  string  $value  Value to validate
     * @param  ?Tag  $root  Root element if specified, null otherwise
     * @param  ?EmptyTag  $current  Current element if specified, null otherwise
     * @return bool True if the validation succeeds, false otherwise
     */
    protected function validateSingleValidator(
        Validator $validator,
        string $value,
        ?Tag $root = null,
        ?EmptyTag $current = null
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ): bool {
        if ($validator instanceof Type) {
            $validator = $validator->getValidator();
        }

        if ($this->shouldForwardContext($validator) === true) {
            try {
                assert($validator instanceof DataAwareValidator || $validator instanceof MultiValidator);

                /**
                 * Every MultiValidator do, or at least MUST implements ProvidesMultipleValidators.
                 * This trait has validateWithContext as an abstract declared method.
                 *
                 * @phpstan-ignore method.notFound
                 */
                return $validator->validateWithContext($value, $root, $current);
            } catch (Exception $e) {
                \trigger_error($e->getMessage());

                return false;
            }
        } else {
            return $validator->validate($value);
        }
    }
}
