<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Exceptions\InvalidElementException;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;

/**
 * An "Any" element is any tag outside of the scope of this namespace.
 */
class Any extends SchemaElement
{
    /**
     * Tag element classname, or null for an empty string
     *
     * @var ?class-string<EmptyTag>
     */
    public readonly ?string $ref;

    /**
     * Tag element namespace
     */
    public readonly ?string $namespace;

    /**
     * String value: `true` to treat contents in the renderer, `false` otherwise
     */
    public readonly ?string $processContents;

    /**
     * Creates a new Any element.
     *
     * @param  ?class-string<EmptyTag>  $ref  Tag element classname
     * @param  ?string  $namespace  Tag element namespace
     * @param  ?string  $processContents  `true` to treat contents in the renderer, `false` otherwise
     * @param  int|null  $minOccurs  Minimum occurences
     * @param  int|string|null  $maxOccurs  Maximum occurences
     *
     * @throws InvalidElementException
     */
    public function __construct(
        ?string $ref = null,
        ?string $namespace = null,
        ?string $processContents = null,
        ?int $minOccurs = null,
        int|string|null $maxOccurs = null
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ) {
        /**
         * Not sure to treat PHPDoc type as certain here.
         *
         * @phpstan-ignore function.alreadyNarrowedType, identical.alwaysFalse, booleanAnd.alwaysFalse
         */
        if ($ref !== null && \is_subclass_of($ref, EmptyTag::class) === false) {
            throw new InvalidElementException('Any reference must be a subclass of EmptyTag');
        }
        $this->ref = $ref;
        $this->namespace = $namespace;
        $this->processContents = $processContents;
        parent::__construct($minOccurs, $maxOccurs);
    }
}
