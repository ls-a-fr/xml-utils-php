<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml;

use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use RuntimeException;
use XMLWriter;

/**
 * Wrapper for xmlwriter_* functions
 */
class XmlWriterWrapper
{
    /**
     * XMLWriter instance
     */
    private XMLWriter $xw;

    /**
     * A namespace to prefix every tag with
     */
    private ?XmlNamespace $globalNamespace = null;

    /**
     * Keep track of namespaces declarations on tags
     *
     * @var array<int, \Lsa\Xml\Utils\Xml\XmlNamespace[]>
     */
    private array $namespaceDeclarations = [];

    /**
     * Indexer on namespaceDeclarations
     */
    private int $depth = 0;

    /**
     * Creates a new XmlWriterWrapper
     *
     * @param  bool  $indent  Has indent
     * @param  string  $indentCharacter  Character used to indent
     * @param  string  $xmlVersion  XML version used
     * @param  string  $encoding  XML encoding
     *
     * @throws RuntimeException If XMLWriter fails to open
     */
    public function __construct(
        bool $indent = true,
        string $indentCharacter = ' ',
        string $xmlVersion = '1.0',
        string $encoding = 'UTF-8'
    ) { // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
        $xw = xmlwriter_open_memory();
        if ($xw === false) {
            throw new RuntimeException('Could not open XMLWriter. Please check your configuration');
        }
        $this->xw = $xw;

        if ($indent === true) {
            $this->setIndent($indentCharacter);
        }
        $this->startDocument($xmlVersion, $encoding);
    }

    /**
     * Returns the XML contents
     *
     * @return string XML contents
     */
    public function asXml(): string
    {
        xmlwriter_end_document($this->xw);

        return xmlwriter_output_memory($this->xw);
    }

    /**
     * Sets a global namespace to render every tag
     *
     * @param  \Lsa\Xml\Utils\Xml\XmlNamespace  $ns  The namespace used
     */
    public function setGlobalNamespace(XmlNamespace $ns): void
    {
        $this->globalNamespace = $ns;
    }

    /**
     * Opens a tag
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\EmptyTag  $node  Tag to open
     */
    public function openTag(EmptyTag $node): void
    {
        $this->depth++;
        // If namespaced
        $this->handleNamespaces($node);
        $namespace = $this->findNamespace($node);

        $this->doOpenTag($namespace, $node);
        $this->addTagAttributes($namespace, $node);
    }

    /**
     * Utility method to make `openTag` lighter. Handle the tag creation on XMLWriter.
     *
     * @param  ?XmlNamespace  $namespace  Namespace of this tag, if any
     * @param  EmptyTag  $node  The tag to open
     */
    protected function doOpenTag(?XmlNamespace $namespace, EmptyTag $node): void
    {
        // Depth check to prevent duplicate namespace
        if ($namespace !== null) {
            // This tag has a namespace
            // Supply URI only if namespace is declared on this node, otherwise null
            $uri = null;
            $nsFromTag = $this->findNamespaceOnDepth($node, $this->depth);
            // Better safe than sorry, but this call to compareTo does not seem necessary
            if ($nsFromTag !== null && $namespace->compareTo($nsFromTag) === true) {
                $uri = $nsFromTag->uri;
            }
            xmlwriter_start_element_ns($this->xw, $namespace->prefix, $node->localName, $uri);
        } elseif (str_starts_with($node->getTagName(), 'xml:') === true) {
            xmlwriter_start_element_ns($this->xw, 'xml', $node->localName, null);
        } else {
            xmlwriter_start_element($this->xw, $node->localName);
        }
    }

    /**
     * Utility method to make `openTag` lighter. Handle attribute setting after tag creation on XMLWriter.
     *
     * @param  ?XmlNamespace  $namespace  Namespace of this tag, if any
     * @param  EmptyTag  $node  The tag to open
     */
    protected function addTagAttributes(?XmlNamespace $namespace, EmptyTag $node): void
    {
        // Attribute handling
        foreach ($node->attributes as $attribute) {
            if ($attribute->localName === $namespace?->prefix && $attribute->value === $namespace->uri) {
                // Already added by xmlwriter_start_element_ns
                continue;
            }

            if ($attribute->prefix !== '' && $attribute->prefix !== 'xmlns') {
                $this->addNamespacedAttribute($attribute);
            } elseif ($attribute->prefix !== '') {
                if ($this->globalNamespace === null || $attribute->localName !== $this->globalNamespace->prefix) {
                    $this->addAttribute($attribute);
                }
                // Do nothing, that would duplicate the namespace
            } else {
                $this->addAttribute($attribute);
            }
        }
    }

    /**
     * Close the current tag
     */
    public function closeTag(): void
    {
        $this->pruneNamespaces();
        $this->depth--;
        xmlwriter_end_element($this->xw);
    }

    /**
     * Write contents at this char point
     *
     * @param  string  $text  Text to write
     */
    public function writeText(string $text): void
    {
        xmlwriter_text($this->xw, $text);
    }

    /**
     * Wrapper function to set both `$indentNumber` and `$indentCharacter`
     *
     * @param  string  $indentCharacter  Character used to indent
     */
    protected function setIndent(string $indentCharacter): void
    {
        xmlwriter_set_indent($this->xw, true);
        xmlwriter_set_indent_string($this->xw, $indentCharacter);
    }

    /**
     * Wrapper function to set both `$xmlVersion` and `$encoding`
     *
     * @param  string  $xmlVersion  XML version used
     * @param  string  $encoding  XML encoding
     */
    protected function startDocument(string $xmlVersion, string $encoding): void
    {
        xmlwriter_start_document($this->xw, $xmlVersion, $encoding);
    }

    /**
     * Add a namespaced attribute (example: prefix:attr="value")
     *
     * @param  \Lsa\Xml\Utils\Xml\Attribute  $attribute  Attribute to add
     */
    protected function addNamespacedAttribute(Attribute $attribute): void
    {
        $ns = $this->findNamespace($attribute);
        if ($ns !== null) {
            xmlwriter_start_attribute_ns($this->xw, $ns->prefix, $attribute->localName, $ns->uri);
            xmlwriter_end_attribute($this->xw);
        } else {
            $this->addAttribute($attribute);
        }
    }

    /**
     * Add an attribute on current tag
     *
     * @param  \Lsa\Xml\Utils\Xml\Attribute  $attribute  Attribute to add
     */
    protected function addAttribute(Attribute $attribute): void
    {
        xmlwriter_start_attribute($this->xw, $attribute->name);
        xmlwriter_text($this->xw, $attribute->value);
        xmlwriter_end_attribute($this->xw);
    }

    /**
     * Lookup namespaces and set current namespace declarations
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\EmptyTag  $node  The current node
     */
    protected function handleNamespaces(EmptyTag $node): void
    {
        $namespaces = $this->lookupNamespaces($node);
        $this->namespaceDeclarations[$this->depth] = $namespaces;
    }

    /**
     * Find a namespace based on a prefixed element
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\EmptyTag|\Lsa\Xml\Utils\Xml\Attribute  $element  Prefixed element
     * @return ?\Lsa\Xml\Utils\Xml\XmlNamespace Found namespace, if any
     */
    protected function findNamespace(EmptyTag|Attribute $element): ?XmlNamespace
    {
        if ($element->prefix === '') {
            return $this->globalNamespace;
        }
        if ($element->prefix === $this->globalNamespace?->prefix) {
            return $this->globalNamespace;
        }

        $aggregatedNamespaces = array_reduce($this->namespaceDeclarations, function ($acc, $nd) {
            return [...$acc, ...$nd];
        }, []);

        return $this->findNamespaceDeclaration($aggregatedNamespaces, $element);
    }

    /**
     * Find an EmptyTag namespace on a specific depth
     *
     * @param  EmptyTag  $element  The namespaced element
     * @param  int  $depth  The specific depth
     */
    protected function findNamespaceOnDepth(EmptyTag $element, int $depth): ?XmlNamespace
    {
        $namespaces = ($this->namespaceDeclarations[$depth] ?? []);
        if ($depth === 1 && $this->globalNamespace !== null) {
            $namespaces[] = $this->globalNamespace;
        }

        foreach ($namespaces as $ns) {
            $built = $this->buildNamespaceFromAttribute($element, $ns->prefix);
            if ($built !== null) {
                return $built;
            }
        }

        return null;
    }

    /**
     * Find a namespace declaration on a specific tag, based on namespace declarations
     *
     * @param  XmlNamespace[]  $namespaces  Declarations to search into
     * @param  EmptyTag|Attribute  $element  Element to check namespace declaration on
     */
    protected function findNamespaceDeclaration(array $namespaces, EmptyTag|Attribute $element): ?XmlNamespace
    {
        foreach ($namespaces as $ns) {
            if ($element->prefix === $ns->prefix) {
                return $ns;
            }
        }

        return null;
    }

    /**
     * Build a namespace from an EmptyTag attribute. Namespaces are declared with an Attribute (XML).
     * Will iterate through every attribute to match the needed one, or null if inexistant.
     *
     * @param  EmptyTag  $element  The tag
     * @param  string  $prefix  The namespace prefix
     */
    protected function buildNamespaceFromAttribute(EmptyTag $element, string $prefix): ?XmlNamespace
    {
        foreach ($element->attributes as $attribute) {
            if ($attribute->prefix !== 'xmlns') {
                continue;
            }
            if ($attribute->localName === $prefix) {
                return new XmlNamespace($attribute->localName, $attribute->value);
            }
        }

        return null;
    }

    /**
     * Get namespace declarations for this tag, as an array
     *
     * @param  \Lsa\Xml\Utils\Xml\Base\EmptyTag  $node  Node to check on
     * @return array<int, \Lsa\Xml\Utils\Xml\XmlNamespace> Declarations
     */
    protected function lookupNamespaces(EmptyTag $node): array
    {
        $namespaces = [];
        foreach ($node->attributes as $attribute) {
            if ($attribute->prefix === 'xmlns') {
                $namespaces[] = new XmlNamespace($attribute->localName, $attribute->value);
            }
        }

        return $namespaces;
    }

    /**
     * Removes any namespace declarations on this depth
     */
    protected function pruneNamespaces(): void
    {
        unset($this->namespaceDeclarations[$this->depth]);
    }
}
