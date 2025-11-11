<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Exceptions\InvalidElementException;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;

/**
 * An element represents any tag in a sequence or a choice.
 * As these validations are compound ones, a SchemaElement has minimum and maximum
 * occurences. To specify "any", do not specify anything.
 * With a minimum occurences, specify "outbounded" as maximum to allow any number of greater occurences.
 *
 * Heavily inspired from XSD structure.
 */
class Element extends SchemaElement
{
    /**
     * Tag element classname
     *
     * @var class-string<EmptyTag>
     */
    public readonly string $ref;

    /**
     * Creates a new Element.
     *
     * @param  class-string<EmptyTag>  $ref  Tag element classname
     * @param  ?int  $minOccurs  Minimum occurences
     * @param  int|string|null  $maxOccurs  Maximum occurences
     *
     * @throws InvalidElementException if `$ref` is not subclass of EmptyTag
     */
    public function __construct(string $ref, ?int $minOccurs = null, int|string|null $maxOccurs = null)
    {
        /**
         * Not sure to treat PHPDoc type as certain here.
         *
         * @phpstan-ignore function.alreadyNarrowedType, identical.alwaysFalse
         */
        if (\is_subclass_of($ref, EmptyTag::class) === false) {
            throw new InvalidElementException('Element reference must be a subclass of EmptyTag');
        }
        $this->ref = $ref;
        parent::__construct($minOccurs, $maxOccurs);
    }
}
