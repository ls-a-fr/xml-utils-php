<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Contracts;

/**
 * Any class that implements a validator can validate a value.
 * Also provides a `getValidator` method to access a inner validator object.
 */
interface Validator
{
    /**
     * Returns the current validator for this instance.
     *
     * @return \Lsa\Xml\Utils\Contracts\Validator $validator  Get the inner validator, or self if none defined
     */
    public function getValidator(): Validator;

    /**
     * Returns the parent validator for this instance, if any.
     *
     * @return \Lsa\Xml\Utils\Contracts\Validator|null $validator  Get the parent validator, or null if none defined
     */
    public function getParentValidator(): ?Validator;

    /**
     * Sets the parent validator for this instance, if needed.
     *
     * @param  \Lsa\Xml\Utils\Contracts\Validator  $parentValidator  The parent validator
     */
    public function setParentValidator(Validator $parentValidator): void;

    /**
     * Uses the linked validator and check supplied value.
     *
     * @param  string  $value  The value to check against
     * @return bool `true` if the test is successful, `false` otherwise
     */
    public function validate(string $value): bool;
}
