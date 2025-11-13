<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation;

use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;

/**
 * A TypedAttribute is a constraint set on an XML attribute.
 * This constraint can be of various flavors: Regex, Enum, Compound...
 */
class TypedAttribute
{
    /**
     * The attribute name
     */
    public readonly string $name;

    /**
     * The validation class name
     *
     * @var class-string<Type>|Type
     */
    public readonly string|Type $type;

    /**
     * Creates a new TypedAttribute
     *
     * @param  string  $name
     * @param  class-string<Type>|Type  $type
     */
    public function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Reconsider a property as required. Evaluates every type isRequired is called.
     *
     * @param  ?EmptyTag  $currentNode  Current node for this property.
     * @return ?bool Null if this function is not defined.
     *
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
     */
    protected function reconsiderRequired(?EmptyTag $currentNode = null): ?bool
    {
        return null;
    }

    /**
     * Checks if this TypedAttribute is required on a specific node
     *
     * @return bool True if this TypedAttribute is mandatory, false otherwise
     */
    public function isRequired(?EmptyTag $currentNode = null): bool
    {
        $evaluation = static::reconsiderRequired($currentNode);
        if ($evaluation !== null) {
            return $evaluation;
        }

        return false;
    }

    /**
     * Unpacks this validator, returns an array containing as a key its name, and as a value its type.
     *
     * @return array<string,class-string<Type>|Type>
     */
    public function unpack(): array
    {
        return [$this->name => $this->type];
    }
}
