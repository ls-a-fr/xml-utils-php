<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

use Lsa\Xml\Utils\Contracts\Validator;

/**
 * A Type that implements its own validation algorithm renders self when
 * calling getValidator.
 */
trait ProvidesSelfValidation
{
    public function getValidator(): Validator
    {
        return $this;
    }
}
