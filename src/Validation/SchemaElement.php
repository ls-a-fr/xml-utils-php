<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

/**
 * A SchemaElement represents any element allowed in a specific tag.
 * As these validations are compound ones, a SchemaElement has minimum and maximum
 * occurences. To specify "any", do not specify anything.
 * With a minimum occurences, specify "outbounded" as maximum to allow any number of greater occurences.
 *
 * Heavily inspired from XSD structure.
 */
abstract class SchemaElement
{
    /**
     * Tag element minimum occurences
     * Default is one in the XSD specification
     */
    public ?int $minOccurs = 1;

    /**
     * Tag element maximum occurences
     * Default is one in the XSD specification
     */
    public int|string|null $maxOccurs = 1;

    /**
     * Creates a new SchemaElement.
     *
     * @param  ?int  $minOccurs  Minimum occurences
     * @param  int|string|null  $maxOccurs  Maximum occurences
     */
    public function __construct(?int $minOccurs = null, int|string|null $maxOccurs = null)
    {
        $this->minOccurs = ($minOccurs ?? 1);
        $this->maxOccurs = ($maxOccurs ?? 1);
    }
}
