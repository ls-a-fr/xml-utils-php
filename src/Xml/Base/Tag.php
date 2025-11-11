<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml\Base;

use Lsa\Xml\Utils\Collections\AttributeCollection;
use Lsa\Xml\Utils\Collections\NodeCollection;
use Lsa\Xml\Utils\Traits\ProvidesChildren;

/**
 * A Tag is a Node that can have attributes and children.
 */
abstract class Tag extends EmptyTag
{
    use ProvidesChildren;

    abstract public function getTagName(): string;

    /**
     * Creates a new Tag
     *
     * @param  AttributeCollection  $attributes  Attributes of this tag. Defaults to a new empty Collection
     * @param  string|NodeCollection|null  $children  Children of this tag. Can be a simple string or null.
     */
    public function __construct(
        AttributeCollection $attributes = new AttributeCollection(),
        string|NodeCollection|null $children = new NodeCollection()
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ) {
        parent::__construct($attributes);

        if (is_string($children) === true) {
            $children = (new NodeCollection())->add(new TextNode($children));
        } elseif ($children === null) {
            $children = new NodeCollection();
        }
        $this->children = $children;
        foreach ($this->getChildren() as $child) {
            $child->parent = $this;
        }
    }

    public function content(string $content): static
    {
        $this->children->clean();
        $this->children->add(new TextNode($content));

        return $this;
    }

    public function getContent(): NodeCollection|string|null
    {
        return $this->children;
    }
}
