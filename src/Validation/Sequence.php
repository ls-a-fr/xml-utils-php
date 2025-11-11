<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Collections\SchemaElementCollection;

/**
 * A sequence is a list of available (and often mandatory) nodes in a tag,
 * and they must appear in the specified order.
 * Example: a tag <example> can only have two children: <a> or <b> in this order.
 * We may define three classes: Example, A and B.
 * In this case, Example has a sequence of A and B elements.
 *
 * Heavily inspired from XSD structure.
 */
class Sequence extends SchemaElement
{
    /**
     * Elements allowed in this sequence
     */
    public readonly SchemaElementCollection $elements;

    /**
     * Creates a new Sequence
     *
     * @param  SchemaElement[]  ...$elements  Elements in this Sequence.
     */
    public function __construct(SchemaElement ...$elements)
    {
        parent::__construct();
        $this->elements = new SchemaElementCollection();
        $this->elements->addAll(\array_values($elements));
    }
}
