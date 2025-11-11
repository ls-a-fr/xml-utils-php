<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Contracts;

interface MultiValidator
{
    /**
     * Checks whether this MultiValidator needs a context for any inner validator
     *
     * @return bool True if this Validator needs context, false otherwise
     */
    public function needsContext(): bool;
}
