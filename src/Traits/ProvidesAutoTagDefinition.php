<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

/**
 * Auto-defines the tag name from its class name. This trait is not used in this package, but will
 * in dependencies.
 *
 * @phpstan-ignore trait.unused
 */
trait ProvidesAutoTagDefinition
{
    public function getTagName(): string
    {
        if (in_array(HasName::class, class_uses($this)) === true) {
            $fullClassName = $this->name;
        } else {
            $fullClassName = get_class($this);
        }
        $classParts = explode('\\', $fullClassName);
        $className = array_pop($classParts);
        $className = preg_replace('/([A-Z])/', '-$1', $className);
        $kebabizedClassName = ltrim(strtolower($className), '-');

        return $kebabizedClassName;
    }
}
