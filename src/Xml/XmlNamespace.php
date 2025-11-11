<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml;

/**
 * Represents an XML namespace
 */
class XmlNamespace
{
    /**
     * Creates a new XmlNamespace
     *
     * @param  string  $prefix  Prefix used in this tag
     * @param  string  $uri  Namespace URI
     */
    public function __construct(
        public readonly string $prefix,
        public readonly string $uri
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ) {}

    /**
     * Compares two namespaces.
     *
     * @param  ?XmlNamespace  $namespace  Namespace to compare with current namespace
     * @return bool True if namespace matches, false otherwise
     */
    public function compareTo(?XmlNamespace $namespace): bool
    {
        if ($namespace === null) {
            return false;
        }

        return $namespace->prefix === $this->prefix &&
            $namespace->uri === $this->uri;
    }
}
