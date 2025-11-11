<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Contracts;

use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * Allows to access root element from another class.
 * A DataAware Type will have the `setRootElement` called in the validation process.
 */
interface DataAware
{
    /**
     * Supplies root element to this validator.
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\Tag  $root  The root element
     */
    public function setRootElement(Tag $root): void;

    /**
     * Supplies current element to this validator.
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\EmptyTag  $current  The current element
     */
    public function setCurrentElement(EmptyTag $current): void;
}
