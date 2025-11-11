<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

/**
 * Types can have a name. Usually this name is handled by the class name of this Type, but
 * it can be defined for Union, Compound etc. This trait is not used in this package, but will
 * in dependencies.
 *
 * @phpstan-ignore trait.unused
 */
trait HasName
{
    /**
     * The name used in XSD file
     */
    public string $name;

    /**
     * Sets the name for this Type. This name will go through the getName() method in Profile.
     *
     * @param  string  $name  The name
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
