<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml\Base;

use Lsa\Xml\Utils\Collections\NodeCollection;
use Lsa\Xml\Utils\Traits\ProvidesTraversal;
use Lsa\Xml\Utils\Xml\ExtendedXMLElement;
use Lsa\Xml\Utils\Xml\XmlDocument;

/**
 * Base class for an XML node
 * This class provides several methods unavailable in SimpleXMLElement, such as:
 * - Traversal: easy access to parent(), root(), ancestor($className)
 * - Data Context: allows to set object properties on nodes, that will not be rendered
 * - shouldBeRendered(): allows to disable a node from the tree
 */
abstract class Node
{
    use ProvidesTraversal;

    /**
     * Data context of this node
     *
     * @var array<string,mixed>
     */
    private array $data = [];

    /**
     * `true` if we should render this node, `false` otherwise
     */
    private bool $renderable = true;

    /**
     * Returns contents of a node.
     * Will return different content types based on the node subclass.
     */
    abstract public function getContent(): NodeCollection|string|null;

    /**
     * Set a piece of data context to a node
     *
     * @param  string  $key  The data key
     * @param  mixed  $value  Any value
     */
    public function data(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Access a piece of data context in this node
     *
     * @param  string  $key  The data key
     * @param  mixed  $defaultValue  Value if this key is not found
     * @return mixed The data
     */
    public function getData(string $key, mixed $defaultValue = null): mixed
    {
        // phpcs:disable Squiz.Formatting.OperatorBracket.MissingBrackets
        return $this->data[$key] ?? $defaultValue;
    }

    /**
     * Gets the renderable state of this node
     *
     * @return bool `true` if the node should be rendered, `false` otherwise
     */
    public function shouldBeRendered(): bool
    {
        return $this->renderable;
    }

    /**
     * Evaluates this tag, allowing to fine tune its representation
     * May be overriden as it results in a no-operation in base class
     */
    // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    // phpcs:disable PEAR.Functions.FunctionDeclaration.BraceOnSameLine
    public function evaluate(): void {}

    /**
     * Marks this node as unrenderable, thus removing it from the XML tree
     */
    public function markAsUnrenderable(): void
    {
        $this->renderable = false;
    }

    /**
     * Marks this node as renderable, thus adding it back from the XML tree.
     * This method should only be used if the node was previously flagged as underenderable.
     */
    public function markAsRenderable(): void
    {
        $this->renderable = true;
    }

    /**
     * Returns this node as an XML element
     *
     * @return \Lsa\Xml\Utils\Xml\ExtendedXMLElement The XML element
     */
    public function asXmlElement(): ExtendedXMLElement
    {
        return new ExtendedXMLElement($this->asXml());
    }

    /**
     * Returns this node as an XML string
     *
     * @return string The XML representation of this node
     */
    public function asXml(): string
    {
        $xmlDocument = new XmlDocument();
        $xmlDocument->addChild($this);

        return $xmlDocument->getContents();
    }
}
