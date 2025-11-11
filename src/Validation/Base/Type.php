<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Base;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Validators\CompoundValidator;
use Lsa\Xml\Utils\Validation\Validators\CumulativeOrderedValidator;
use Lsa\Xml\Utils\Validation\Validators\CumulativeValidator;
use Lsa\Xml\Utils\Validation\Validators\UnionValidator;

/**
 * A Type is a contraint on an attribute.
 */
abstract class Type implements Validator
{
    /**
     * Current validator, stored in a cache (prevents reinstanciation every type `validate()` method is called)
     */
    private Validator $validatorCache;

    /**
     * Parent validator, if any.
     */
    protected ?Validator $parentValidator;

    /**
     * Returns the parent validator, if any.
     */
    public function getParentValidator(): ?Validator
    {
        if (isset($this->parentValidator) === true) {
            return $this->parentValidator;
        }

        return null;
    }

    /**
     * Defines the parent validator.
     *
     * @param  Validator  $parentValidator  The parent validator
     */
    public function setParentValidator(Validator $parentValidator): void
    {
        $this->parentValidator = $parentValidator;
    }

    /**
     * Validates this type against a value.
     *
     * @param  string  $value  The value to validate
     * @return bool True if the value is validated, false otherwise
     */
    public function validate(string $value): bool
    {
        return $this->getValidator()->validate($value);
    }

    /**
     * Caches the current validator, to improve performance.
     *
     * @param  Validator  $validator  The validator to cache
     * @return Validator The cached validator
     */
    protected function cache(Validator $validator): Validator
    {
        if (isset($this->validatorCache) === false) {
            // Prevent validator modification outside this class
            $this->validatorCache = clone $validator;
        }

        return $this->validatorCache;
    }

    /**
     * Search for a specific validator inside this Type.
     * As a Validator can be Compound, Cumulative, Union, you may want to recover a specific validator
     * inside all this hierarchy.
     *
     * @param  class-string<Validator>  $className  The validator to search, as a class name
     * @return ?Validator The validator instance if found, or null otherwise.
     */
    public function search(string $className): ?Validator
    {
        return $this->searchClassName($className);
    }

    /**
     * Called by `search()`, performs the actual search. Is called recursively.
     *
     * @param  class-string<Validator>  $className  The validator to search, as a class name
     * @return ?Validator The validator instance if found, or null otherwise.
     */
    protected function searchClassName(string $className): ?Validator
    {
        $validator = $this->getValidator();
        // Found
        if (get_class($validator) === $className) {
            return $validator;
        }
        // Provides self-validation, dead-end
        if ($validator === $this) {
            return null;
        }
        if (
            $validator instanceof UnionValidator
            || $validator instanceof CumulativeValidator
            || $validator instanceof CumulativeOrderedValidator
        ) {
            return $this->searchClassNameMultiple($validator, $className);
        }
        if ($validator instanceof CompoundValidator) {
            $innerValidator = $validator->validator;
            if (get_class($innerValidator) === $className) {
                return $innerValidator;
            }

            if ($innerValidator instanceof Type) {
                return $innerValidator->searchClassName($className);
            }

            return null;
        }

        return null;
    }

    /**
     * Utility method to search in UnionValidator, CumulativeValidator or CumulativeOrderedValidator.
     *
     * @param  UnionValidator|CumulativeValidator|CumulativeOrderedValidator  $validator  A validator
     * @param  class-string<Validator>  $className  The validator to search, as a class name
     */
    protected function searchClassNameMultiple(
        UnionValidator|CumulativeValidator|CumulativeOrderedValidator $validator,
        string $className
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ): ?Validator {
        foreach ($validator->validators as $innerValidator) {
            if (get_class($innerValidator) === $className) {
                return $innerValidator;
            }
            if ($innerValidator instanceof Type) {
                $result = $innerValidator->searchClassName($className);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }
}
