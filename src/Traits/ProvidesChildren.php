<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

use Lsa\Xml\Utils\Collections\NodeCollection;
use Lsa\Xml\Utils\Xml\Base\Node;

/**
 * Grants children to an XmlNode
 */
trait ProvidesChildren
{
    /**
     * Children stored in this node
     */
    private readonly NodeCollection $children;

    /**
     * Gets tag children
     */
    public function getChildren(): NodeCollection
    {
        return $this->children;
    }

    /**
     * Adds a child to this node
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\Node  $node  Added node
     * @return static Current node
     */
    public function child(Node $node): static
    {
        $this->children->add($node);
        $node->parent = $this;

        return $this;
    }

    public function prepend(Node $node): static
    {
        $this->children->prepend($node);
        $node->parent = $this;

        return $this;
    }

    public function pushAfter(Node $node, Node|string $reference): static
    {
        $this->children->insertAfter($node, $reference);

        $node->parent = $this;

        return $this;
    }

    /**
     * Adds several children to this node
     *
     * @param  list<\Lsa\Xml\Utils\Xml\Base\Node>|\Lsa\Xml\Utils\Collections\NodeCollection  $nodes  Added nodes
     * @return static Current node
     */
    public function children(array|NodeCollection $nodes): static
    {
        foreach ($nodes as $node) {
            $this->child($node);
        }

        return $this;
    }
}
