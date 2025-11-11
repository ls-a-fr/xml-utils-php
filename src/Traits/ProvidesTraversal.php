<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

use Lsa\Xml\Utils\Collections\NodeCollection;
use Lsa\Xml\Utils\Exceptions\InvalidChildException;
use Lsa\Xml\Utils\Exceptions\InvalidXpathException;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Node;
use Lsa\Xml\Utils\Xml\Base\Tag;
use Lsa\Xml\Utils\Xml\Base\TextNode;
use Lsa\Xml\Utils\Xml\XmlComparator;

/**
 * Provides various methods to read and update nodes by walking through their tree
 */
trait ProvidesTraversal
{
    /**
     * The parent tag, if any
     */
    public ?Tag $parent = null;

    /**
     * Executes an XPath query on the node
     *
     * @param  string  $xpath  The XPath query
     * @return \Lsa\Xml\Utils\Collections\NodeCollection The resulting node collection
     *
     * @throws InvalidXpathException If XPath query generates an error
     */
    public function xpath(string $xpath): NodeCollection
    {
        if (($this instanceof Tag) === false) {
            throw new InvalidXpathException();
        }
        $xmlElement = $this->asXmlElement();

        $results = $xmlElement->xpath($xpath);
        if ($results === false || $results === null) {
            throw new InvalidXpathException('xpath query returned an error. Query was: '.$xpath);
        }

        $foundNodes = new NodeCollection();

        if (empty($results) === true) {
            return $foundNodes;
        }

        $flattenNodes = $this->flattenNodes($this);
        $comparator = new XmlComparator();

        // Find every corresponding Node in the current NodeCollection.
        // This allows to keep references even after SimpleXMLElement transform.
        foreach ($results as $result) {
            foreach ($flattenNodes as $flattenNode) {
                if ($comparator->compareNode($flattenNode->asXmlElement(), $result) === true) {
                    $flattenNodes->remove($flattenNode);
                    $foundNodes->add($flattenNode);
                }
            }
        }

        return $foundNodes;
    }

    /**
     * Utility method to ease lookup in `xpath` method. Lies every node and every children in a
     * NodeCollection.
     *
     * @param  Node  $node  Node to flatten (first call is `$this`)
     * @return NodeCollection Every node including children as a first-level collection
     */
    private function flattenNodes(Node $node): NodeCollection
    {
        $result = new NodeCollection();
        if (($node instanceof Tag) === false) {
            return $result;
        }
        foreach ($node->getChildren() as $child) {
            if ($child instanceof TextNode) {
                continue;
            }
            $result->add($child);
            $result->addAll($this->flattenNodes($child));
        }

        return $result;
    }

    /**
     * Creates each provided tag as child of previous one
     * Example: ["<a>", "<b>", "<c>", "<d>]
     * Result: <a><b><c><d/></c></b></a>
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\Tag|\Lsa\Xml\Utils\Xml\Base\EmptyTag  ...$tags  Variadic list of tags
     * @return list<Tag|EmptyTag> Created tags for further usage
     *
     * @throws InvalidChildException If drilling contains an EmptyTag between other tags
     */
    public function drill(Tag|EmptyTag ...$tags): array
    {
        $currentTag = $this;
        $references = [];
        foreach ($tags as $tag) {
            // Push reference
            $references[] = $tag;

            if (($currentTag instanceof Tag) === false && $currentTag instanceof EmptyTag) {
                throw new InvalidChildException('Cannot add child to orphan tag '.$currentTag->localName);
            }

            assert($currentTag instanceof Tag);
            $currentTag->child($tag);
            $currentTag = $tag;
        }

        return $references;
    }

    /**
     * Returns the root tag of this XML tree
     *
     * @return Tag The root tag
     *
     * @throws InvalidChildException If this tag has no parent
     */
    public function root(): Tag
    {
        $start = $this;
        if (($start instanceof Tag) === false && $start->parent() === null) {
            throw new InvalidChildException('
                This node is not attached to a tree (has no parents), 
                cannot call root() method on it.'
            );
        }

        // Then $start must be a Tag or have a parent.
        // And parent returns Tag instances.
        while ($start->parent() !== null && $start !== $start->parent()) {
            $start = $start->parent();
        }

        assert($start instanceof Tag);

        return $start;
    }

    /**
     * Return the first node parent with the specified class name
     *
     * @param  class-string  $className  Searched class name
     * @return \Lsa\Xml\Utils\Xml\Base\Tag Found tag, if any
     */
    public function ancestor(string $className): ?Tag
    {
        $start = $this;
        while ($start->parent() !== null && ($start->parent() instanceof $className) === false) {
            $start = $start->parent();
        }

        return $start->parent();
    }

    /**
     * Get this tag parent node, if any
     *
     * @return \Lsa\Xml\Utils\Xml\Base\Tag The parent node
     */
    public function parent(): ?Tag
    {
        return $this->parent;
    }

    public function previousSibling(): ?Node
    {
        if ($this->parent() === null) {
            return null;
        }
        $currentIndex = $this->getCurrentIndex();

        // phpcs:disable Squiz.Formatting.OperatorBracket.MissingBrackets
        return $this->parent()->getChildren()->toArray()[($currentIndex - 1)] ?? null;
    }

    public function nextSibling(): ?Node
    {
        if ($this->parent() === null) {
            return null;
        }
        $currentIndex = $this->getCurrentIndex();

        // phpcs:disable Squiz.Formatting.OperatorBracket.MissingBrackets
        return $this->parent()->getChildren()->toArray()[($currentIndex + 1)] ?? null;
    }

    public function getCurrentIndex(): ?int
    {
        if ($this->parent() === null) {
            return 0;
        }

        return $this->parent()->getChildren()->getIndex($this);
    }
}
