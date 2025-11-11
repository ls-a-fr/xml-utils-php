<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml\Base;

use Lsa\Xml\Utils\Collections\NodeCollection;

/**
 * An XML text node
 */
class TextNode extends Node
{
    /**
     * Node contents
     */
    public readonly string $contents;

    /**
     * Creates a new TextNode
     *
     * @param  string  $contents  The node contents
     */
    public function __construct(string $contents)
    {
        $this->contents = $contents;
    }

    public function getContent(): NodeCollection|string|null
    {
        return $this->contents;
    }
}
