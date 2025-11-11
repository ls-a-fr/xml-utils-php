<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Collections;

use Lsa\Xml\Utils\Validation\SchemaElement;

/**
 * Collection of SchemaElements
 *
 * @extends Collection<SchemaElement>
 */
class SchemaElementCollection extends Collection
{
    public function get(string $type): static
    {
        return $this->filter(fn ($t) => get_class($t) == $type);
    }
}
