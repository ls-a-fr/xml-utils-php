<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

use Lsa\Xml\Utils\Validation\TagValidator;

/**
 * Allows a Node to be validated.  This trait is not used in this package, but will
 * in dependencies.
 *
 * @phpstan-ignore trait.unused
 */
trait ProvidesValidation
{
    /**
     * TagValidator factory
     */
    public readonly TagValidator $validator;

    public function validate(): bool
    {
        $this->validator ??= TagValidator::make();
        $result = $this->validator->validate($this);

        if ($result === false) {
            foreach ($this->validator->getErrors() as $error) {
                trigger_error($error);
            }
        }

        return $result;
    }
}
