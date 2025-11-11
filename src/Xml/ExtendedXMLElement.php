<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml;

use SimpleXMLElement;

/**
 * SimpleXMLElement enriched with the `applyNamespaces` method, to
 * declare an "x" namespace.
 */
class ExtendedXMLElement extends SimpleXMLElement
{
    /**
     * Apply namespaces on the current node
     */
    public function applyNamespaces(): void
    {
        $namespaces = $this->getDocNamespaces();
        if ($namespaces === false) {
            return;
        }
        foreach ($namespaces as $strPrefix => $strNamespace) {
            if (strlen($strPrefix) === 0) {
                // Assign an arbitrary namespace prefix.
                $strPrefix = 'x';
            }
            $this->registerXPathNamespace($strPrefix, $strNamespace);
        }
    }
}
