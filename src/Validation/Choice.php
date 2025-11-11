<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Collections\SchemaElementCollection;

/**
 * A choice is a list of available nodes in a tag. They may appear in any
 * order. Usually, at least one element must be declared.
 * Example: a tag `<example>` can only have two children: `<a>` or `<b>`.
 * We may define three classes: Example, A and B.
 * In this case, Example has a choice of A and B elements.
 *
 * Heavily inspired from XSD structure.
 */
class Choice extends SchemaElement
{
    /**
     * Elements allowed in this choice
     */
    public readonly SchemaElementCollection $elements;

    /**
     * Creates a new Choice.
     *
     * @param  SchemaElement[]  ...$elements  Elements in this Choice.
     */
    public function __construct(SchemaElement ...$elements)
    {
        parent::__construct();
        $this->elements = (new SchemaElementCollection())->addAll(\array_values($elements));
    }

    /**
     * Sets minimum child nodes
     *
     * @param  int|null  $minOccurs  Minimum occurences
     */
    public function minOccurs(?int $minOccurs): static
    {
        $this->minOccurs = $minOccurs;

        return $this;
    }

    /**
     * Sets maximum child nodes
     *
     * @param  int|string|null  $maxOccurs  Maximum occurences
     */
    public function maxOccurs(int|string|null $maxOccurs): static
    {
        $this->maxOccurs = $maxOccurs;

        return $this;
    }
}
