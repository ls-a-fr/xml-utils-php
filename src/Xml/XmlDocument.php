<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml;

use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Node;
use Lsa\Xml\Utils\Xml\Base\Tag;
use Lsa\Xml\Utils\Xml\Base\TextNode;
use RuntimeException;

/**
 * Represents an XML document and offers a convenient wrapper to write XML contents
 */
class XmlDocument
{
    /**
     * XML Writer wrapper used to do the actual writing
     */
    private XmlWriterWrapper $xml;

    /**
     * Creates a new XmlDocument.
     *
     * @param  bool  $indent  Has indent
     * @param  string  $indentCharacter  Character used to indent
     * @param  string  $xmlVersion  XML version used
     * @param  string  $encoding  XML encoding
     */
    public function __construct(
        bool $indent = true,
        string $indentCharacter = ' ',
        string $xmlVersion = '1.0',
        string $encoding = 'UTF-8'
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ) {
        $this->xml = new XmlWriterWrapper($indent, $indentCharacter, $xmlVersion, $encoding);
    }

    /**
     * Sets a global namespace to render every tag
     *
     * @param  \Lsa\Xml\Utils\Xml\XmlNamespace  $ns  The namespace used
     */
    public function setGlobalNamespace(XmlNamespace $ns): void
    {
        $this->xml->setGlobalNamespace($ns);
    }

    /**
     * Get the document contents
     *
     * @return string The XML contents
     */
    public function getContents(): string
    {
        return $this->xml->asXml();
    }

    /**
     * Add a new node
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\Node  $node  Node to add
     *
     * @throws RuntimeException If no recognized tag is supplied
     */
    public function addChild(Node $node): void
    {
        if ($node instanceof TextNode) {
            $this->addTextNode($node);

            return;
        }
        if ($node instanceof Tag) {
            $this->addElement($node);

            return;
        }
        if ($node instanceof EmptyTag) {
            $this->addEmptyTag($node);

            return;
        }
        throw new RuntimeException('Invalid node '.$node->asXml());
    }

    /**
     * Add a text node (example: hello)
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\TextNode  $node  Node to add
     */
    public function addTextNode(TextNode $node): void
    {
        if ($node->shouldBeRendered() === false) {
            return;
        }
        /**
         * TextNode contents is always string
         *
         * @var string $content
         */
        $content = $node->getContent();
        $this->xml->writeText($content);
    }

    /**
     * Add a tag (example: <tag></tag>)
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\Tag  $node  Node to add
     */
    public function addElement(Tag $node): void
    {
        if ($node->shouldBeRendered() === false) {
            return;
        }

        // Open tag
        $this->xml->openTag($node);
        // Contents writing
        foreach ($node->getChildren() as $child) {
            $this->addChild($child);
        }
        // Close tag
        $this->xml->closeTag();
    }

    /**
     * Add an tag (example: <tag/>)
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\EmptyTag  $node  Node to add
     */
    public function addEmptyTag(EmptyTag $node): void
    {
        if ($node->shouldBeRendered() === false) {
            return;
        }

        // Open and close tag
        $this->openAndCloseTag($node);
    }

    /**
     * Wrapper function to open and close an orphan tag
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\EmptyTag  $node  Node to add
     */
    protected function openAndCloseTag(EmptyTag $node): void
    {
        if ($node->shouldBeRendered() === false) {
            return;
        }

        $this->xml->openTag($node);
        $this->xml->closeTag();
    }
}
