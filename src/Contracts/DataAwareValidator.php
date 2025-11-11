<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Contracts;

use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * Allows to access root element from another class.
 * A DataAware Type will have the `validateWithContext` method called in the validation process.
 */
interface DataAwareValidator
{
    /**
     * Validates a value based on its context
     *
     * @param  string  $value  Value to be validated
     * @param  ?Tag  $root  Root tag, if necessary
     * @param  ?EmptyTag  $current  Current tag, if necessary
     * @return bool True if value validates this constraint, false otherwise.
     */
    public function validateWithContext(string $value, ?Tag $root, ?EmptyTag $current): bool;
}
