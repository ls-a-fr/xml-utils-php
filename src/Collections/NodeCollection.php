<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Collections;

use Lsa\Xml\Utils\Exceptions\InvalidCollectionOperationException;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Node;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * Collection of Nodes
 *
 * @extends Collection<Node>
 */
class NodeCollection extends Collection
{
    public function prepend(Node $tag): static
    {
        \array_unshift($this->data, $tag);

        return $this;
    }

    public function insertAfter(Node $tag, Node|string $reference): static
    {
        if (\is_string($reference) === true) {
            if (\is_subclass_of($reference, Tag::class) === true) {
                $search = $this->filter(fn ($n) => get_class($n) === $reference);
            } else {
                $search = $this->get($reference);
            }
            $index = null;
            if ($search->isEmpty() === false) {
                /**
                 * Method getIndex is called only if search is not empty, thus $search->first() cannot
                 * return null.
                 *
                 * @phpstan-ignore argument.type
                 */
                $index = $this->getIndex($search->first());
            }
        } else {
            $index = $this->getIndex($reference);
        }
        if ($index === null) {
            if (\is_string($reference) === false) {
                $reference = get_class($reference);
            }
            throw new InvalidCollectionOperationException('Cannot find '.$reference.' in current NodeCollection');
        }
        \array_splice($this->data, ($index + 1), 0, [$tag]);

        return $this;
    }

    /**
     * Returns node collection based on a node name
     *
     * @param  string  $name  Node name
     */
    public function get(string $name): static
    {
        return $this->filter(fn ($t) => $t instanceof EmptyTag && $t->getTagName() == $name);
    }

    /**
     * Checks if any renderable element exists in this Collection.
     *
     * @return bool True if at least one element is renderable, false otherwise.
     */
    public function hasAnyRenderable(): bool
    {
        foreach ($this->data as $child) {
            if ($child->shouldBeRendered() === true) {
                return true;
            }
        }

        return false;
    }
}
