<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml;

use Lsa\Xml\Utils\Exceptions\InvalidXpathException;
use SimpleXMLElement;

/**
 * Compares recursively two XML nodes
 */
class XmlComparator
{
    /**
     * Compares two node trees with reversal
     * (A node present in the first XMLElement should be present in the second,
     * and vice-versa)
     */
    public const VALIDATION_STRICT = 1;

    /**
     * Default. Compares two node trees in a case-insensitive manner. Only missing
     * nodes in the first XMLElement will be checked.
     */
    public const VALIDATION_LAX = 2;

    /**
     * Compares with swappable attribute values. Example: This two nodes are considered
     * equivalent:
     * - <fo:block list="a b c"/>
     * - <fo:block list="c b a"/>
     */
    public const VALIDATION_SWAPPABLE_ATTRIBUTE_VALUES = 4;

    /**
     * Errors returned after a comparison
     *
     * @var list<string>
     */
    private array $errors = [];

    /**
     * Chosen validation mode
     */
    public readonly int $mode;

    /**
     * Creates a new XmlComparator
     *
     * @param  int  $mode  Chosen validation mode
     */
    public function __construct(int $mode = self::VALIDATION_LAX)
    {
        $this->mode = $mode;
    }

    /**
     * Get errors raised by this comparator
     *
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Resets all errors raised by this comparator
     */
    public function reset(): void
    {
        $this->errors = [];
    }

    /**
     * Compare two array of nodes. This is useful after an XPath expression
     *
     * @param  list<SimpleXMLElement>  $nodes1  The first array of nodes
     * @param  list<SimpleXMLElement>  $nodes2  The second array of nodes
     * @param  bool  $callNext  Prevent infinite loop when using strict validation
     * @return bool Whether these two arrays contain the same nodes
     */
    public function compareNodes(array $nodes1, array $nodes2, bool $callNext = true): bool
    {
        // Check for difference in node count only in strict mode
        if (count($nodes1) !== count($nodes2)) {
            $this->errors[] = 'Strict mode: Length of first array ('.count($nodes1).') is different 
                from second array ('.count($nodes2).')';
        }

        // Compare every node
        $result = true;
        foreach ($nodes1 as $node1) {
            if ($this->compareNode($node1, $this->fetchNodeFromArray($node1, $nodes2)) === false) {
                $this->errors[] = 'Node '.$this->xmlAsStr($node1).' has no match in nodes2';
                $result = false;
            }
        }

        // Stop here if mode VALIDATION_STRICT is not set
        // phpcs:disable Squiz.Operators.ComparisonOperatorUsage.ImplicitTrue
        // phpcs:disable Squiz.Formatting.OperatorBracket.MissingBrackets
        if ($this->mode & self::VALIDATION_STRICT) {
            return $result;
        }

        // Compare nodes in reversal (only in strict mode)
        if ($callNext === true && $this->compareNodes($nodes2, $nodes1, false) === false) {
            $this->errors[] = 'Strict mode: Error in reverse comparison';

            return false;
        }

        return $result;
    }

    /**
     * Compare two nodes.
     *
     * @param  SimpleXMLElement  $node1  The first node
     * @param  SimpleXMLElement  $node2  The second node
     * @return bool Whether the two nodes are equal
     */
    public function compareNode(?SimpleXMLElement $node1, ?SimpleXMLElement $node2): bool
    {
        // Easy check, are they both null or not
        if ($node1 === null && $node2 === null) {
            return true;
        }
        // Easy check, are they null or not
        if ($node1 === null || $node2 === null) {
            $this->errors[] = 'Node 1 or Node 2 is empty';

            return false;
        }
        // Easy check, have they the same node name
        if ($node1->getName() !== $node2->getName()) {
            return false;
        }
        // Compares attributes in a lower-case manner
        if ($this->compareAttributesLowerCase($node1, $node2) === false) {
            $this->errors[] = 'Compare attributes lower case failed';

            return false;
        }

        if ($this->compareChildren($node1, $node2) === false) {
            $this->errors[] = 'Compare children failed';

            return false;
        }

        // Reversal-compare only in strict mode
        // phpcs:disable Squiz.Operators.ComparisonOperatorUsage.ImplicitTrue
        // phpcs:disable Squiz.Formatting.OperatorBracket.MissingBrackets
        if ($this->mode & self::VALIDATION_STRICT) {
            if ($this->compareAttributesLowerCase($node2, $node1) === false) {
                return false;
            }
            if ($this->compareChildren($node2, $node1) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Search for a node in a SimpleXMLElement. In case of multiple-occurences, return the one
     * targeted by the supplied `$index`.
     *
     * @param  SimpleXMLElement  $theNode  Wanted node
     * @param  SimpleXMLElement  $theParent  The node where to search in
     * @param  int  $index  Position-based clue
     * @return ?SimpleXMLElement The found node, if any
     */
    public function guessNode(SimpleXMLElement $theNode, SimpleXMLElement $theParent, int $index): ?SimpleXMLElement
    {
        $results = $this->fetchNodes($theNode, $theParent);
        if (empty($results) === true) {
            return null;
        }
        if (count($results) === 1) {
            return array_pop($results);
        }

        return $results[$index] ?? null;
    }

    /**
     * Based on a node (A), fetch corresponding nodes (A') in another node (B).
     * Will perform an XPath expression, in a lowercase manner, with or without
     * attribute value swapping, based on the supplied `$mode`.
     *
     * @param  SimpleXMLElement  $theNode  Wanted node (A)
     * @param  SimpleXMLElement  $theParent  Node to search in (B)
     * @return SimpleXMLElement[] XPath results
     *
     * @throws InvalidXpathException If XPath query threw an error
     */
    public function fetchNodes(SimpleXMLElement $theNode, SimpleXMLElement $theParent): array
    {
        $xpath = '';

        $parentNamespaces = $theNode->getNamespaces();
        $nodePrefix = \array_key_first($theNode->getNamespaces(false));

        if ($nodePrefix !== null && $nodePrefix !== 0 && $nodePrefix !== '') {
            $xpath = $nodePrefix.':';
        } elseif (\array_key_exists('', $parentNamespaces) === true) {
            // Fix namespace
            $theParent->registerXPathNamespace('x', $parentNamespaces['']);
            $xpath .= 'x:';
        } elseif ($theParent instanceof ExtendedXMLElement) {
            $theParent->applyNamespaces();
        }
        $xpath .= $theNode->getName();
        $xpath .= implode('', array_map(function ($attribute) use ($theNode) {
            $attributeName = $attribute->getName();
            $attributeValue = (string) $attribute;

            // Exclude regular expressions
            if ($theNode->getName() === 'pattern') {
                return '[@'.$attributeName.'="'.$attributeValue.'"]';
            }

            // phpcs:disable Squiz.Operators.ComparisonOperatorUsage.ImplicitTrue
            // phpcs:disable Squiz.Formatting.OperatorBracket.MissingBrackets
            if ($this->mode & self::VALIDATION_SWAPPABLE_ATTRIBUTE_VALUES) {
                // Special check for swappable is done after the XPath query, because
                // PHP does not support XPATH 2.0 (thus no matches function)
                if (str_contains($attributeValue, ' ') === true) {
                    $parts = explode(' ', $attributeValue);

                    // We need to check if these values are different between on another
                    // Or else, we might check multiple types for the same value.
                    // In this case, XPath match multiple times the same token, and
                    // fails to grab the best value.
                    // Example: "center center" will match "bottom center" (first in enumeration).
                    if (array_unique($parts) === $parts) {
                        $regex = implode(' and ', array_map(
                            fn ($p) => 'contains('.implode(',', [
                                $this->xpathLowerCase('@'.$attributeName),
                                $this->xpathLowerCase('"'.$p.'"'),
                            ]).')',
                            $parts
                        ));

                        return '['.$regex.']';
                    }
                }
            }

            // Lower-case check for values
            return '['.$this->xpathLowerCase('@'.$attributeName).'="'.strtolower($attributeValue).'"]';
        }, [...$theNode->attributes()]));

        $xpathResult = $theParent->xpath($xpath);
        if ($xpathResult === false) {
            throw new InvalidXpathException('XPath query caused an error: '.$xpath);
        }
        if ($xpathResult === null || empty($xpathResult)) {
            return [];
        }

        return $xpathResult;
    }

    /**
     * Utility method to craft XPath query. Will match any string element with its lowercase version.
     *
     * @param  string  $element  Element to check on
     * @return string XPath translate function
     */
    protected function xpathLowerCase(string $element): string
    {
        return 'translate('.$element.',"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")';
    }

    /**
     * Get the supplied node in supplied array. Attributes are compared in a
     *
     * @param  SimpleXMLElement  $theNode  Wanted node
     * @param  SimpleXMLElement[]  $nodes  Nodes to search in
     * @return ?SimpleXMLElement Found node, if any
     */
    public function fetchNodeFromArray(SimpleXMLElement $theNode, array $nodes): ?SimpleXMLElement
    {
        $foundNode = null;
        foreach ($nodes as $node) {
            // Compare attributes
            if ($this->compareAttributesLowerCase($theNode, $node, true) === false) {
                continue;
            }
            $foundNode = $node;
            break;
        }

        // If no node found here, it's over
        if ($foundNode === null) {
            return null;
        }

        // Check node contents to prevent false-positives
        $foundNodeContents = trim((string) $foundNode);
        $theNodeContents = trim((string) $theNode);

        if ($foundNodeContents !== $theNodeContents) {
            return null;
        }

        return $foundNode;
    }

    /**
     * Compare two node attributes in a lower-case manner. This method will check
     * for any pattern (regular-expression) to let them untouched.
     *
     * @param  SimpleXMLElement  $node1  The first node
     * @param  SimpleXMLElement  $node2  The seconde node
     * @return bool Whether these two nodes' attributes are considered equal or not
     */
    public function compareAttributesLowerCase(
        ?SimpleXMLElement $node1,
        ?SimpleXMLElement $node2,
        bool $silent = true
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ): bool {
        // Do not validate if any node is null
        if ($node1 === null || $node2 === null) {
            return false;
        }
        // Get all attributes as an array
        $node1Attributes = array_map(fn ($a) => (string) $a, [...$node1->attributes()]);
        $node2Attributes = array_map(fn ($a) => (string) $a, [...$node2->attributes()]);

        // Lowercase them
        $lcNode1Attributes = $this->lowercaseAttributes($node1Attributes);
        $lcNode2Attributes = $this->lowercaseAttributes($node2Attributes);

        $success = true;
        foreach ($lcNode1Attributes as $i => $lcAttribute) {
            // Check attribute is set
            if (in_array($lcAttribute, $lcNode2Attributes) === false) {
                $this->errors[] = $lcAttribute.' is not in array ['.implode(',', $lcNode2Attributes).']';

                return false;
            }
            // Get values. String cast is done to get the attribute value.
            $firstValue = (string) $node1Attributes[$i];
            // Find the value based on the exact case
            $j = array_search($lcAttribute, $lcNode2Attributes);
            $secondValue = (string) ($node2Attributes[$j] ?? '');

            // Do the comparison
            $isRegularExpression = $node1->getName() == 'pattern' && $lcAttribute == 'value';
            if ($this->compareAttribute($firstValue, $secondValue, $isRegularExpression) === false) {
                if ($silent === false) {
                    $this->errors[] = 'Compared Attribute failed: ['.$firstValue.'] vs ['.$secondValue.']';
                }
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Compare children of two node elements
     *
     * @param  SimpleXMLElement  $node1  The first node
     * @param  SimpleXMLElement  $node2  The second node
     * @return bool Whether children are equal
     */
    private function compareChildren(SimpleXMLElement $node1, SimpleXMLElement $node2): bool
    {
        // Get all children, any namespace.
        $node1Children = [];
        foreach (['' => null, ...$node1->getNamespaces()] as $ns) {
            foreach ($node1->children($ns) as $child) {
                $node1Children[] = $child;
            }
        }
        // Without child, no need to continue.
        if (empty($node1Children) === true) {
            return true;
        }

        $i = 0;
        foreach ($node1Children as $child) {
            $childName = $child->getName();
            // Skip annotation tags
            if ($childName === 'annotation') {
                continue;
            }
            // Get the corresponding node in $node2 children
            $xsdNode = $this->guessNode($child, $node2, $i);
            // Compare them
            if ($this->compareNode($child, $xsdNode) === false) {
                $this->errors[] = 'Compare node failed: '.$this->xmlAsStr($child).' vs '.$this->xmlAsStr($xsdNode);

                return false;
            }
            $i++;
        }

        return true;
    }

    /**
     * Checks for attribute values in a swappable order.
     * This allows to consider true a comparison between these two:
     * ```xml
     *  <fo:element memberTypes="a b c"/>
     *  <fo:element memberTypes="b c a"/>
     * ```
     * This method is not used when comparing regular expressions
     *
     * @param  string  $value1  First attribute value string
     * @param  string  $value2  Second attribute value string
     * @return bool Whether they are considered equal or not
     */
    private function checkSwappableAttributeValues(string $value1, string $value2): bool
    {
        // Split attribute values based on space separator, and remove any empty element
        $value1Parts = array_filter(explode(' ', $value1), fn ($p) => trim($p) != '');
        $value2Parts = array_filter(explode(' ', $value2), fn ($p) => trim($p) != '');

        // Check for difference in count, which prevents further comparison
        if (count($value1Parts) !== count($value2Parts)) {
            return false;
        }
        // Sort the two arrays to make it easier to compare
        sort($value1Parts);
        sort($value2Parts);

        // Swappable attributes are checked in a lowercase manner
        foreach ($value1Parts as $j => $valuePart) {
            if (strtolower($valuePart) !== strtolower($value2Parts[$j])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compare two attribute values.
     * - In a case of regular expression, comparison will be done in a case-sensitive manner.
     * - When mode is set to VALIDATION_SWAPPABLE_ATTRIBUTE_VALUES, value array is checked in any order
     * - Otherwise, this comparison is done in a case-insensitive manner.
     *
     * @param  string  $value1  The first value to compare from
     * @param  string  $value2  The second value to compare to
     * @return bool Whether these attributes are regular expressions or not.
     */
    private function compareAttribute(string $value1, string $value2, bool $isRegularExpression)
    {
        if ($isRegularExpression === true) {
            // This is a regular expression, we must check in a case-sensitive manner
            return $value1 === $value2;
        }
        if ($this->mode & self::VALIDATION_SWAPPABLE_ATTRIBUTE_VALUES) {
            return $this->checkSwappableAttributeValues($value1, $value2);
        }

        return strtolower($value1) === strtolower($value2);
    }

    /**
     * Utility method to display the correct XMLNode as a string in
     * an error message.
     *
     * @param  SimpleXMLElement  $node  The node to be displayed
     * @return string The string representation
     */
    private function xmlAsStr(?SimpleXMLElement $node): string
    {
        if ($node === null) {
            return 'NULL';
        }
        $strs = [];
        $attributes = $node->attributes();
        foreach ($attributes as $name => $value) {
            $strs[] = $name.'="'.$value.'"';
        }
        $content = trim((string) $node);
        $nodeStr = '<'.$node->getName().' '.implode(' ', $strs);
        if ($content) {
            $nodeStr .= '>'.$content.'</'.$node->getName().'>';
        } else {
            $nodeStr .= '/>';
        }

        return $nodeStr;
    }

    /**
     * Returns an array of attribute names to a map containing:
     * - As a key: the attribute name (left untouched)
     * - As a value: the lowercase version of attribute name
     *
     * @param  array<string,string>  $attributes  The attribute names (key/values)
     * @return array<string,string> The lowercase map of attributes
     */
    private function lowercaseAttributes(array $attributes): array
    {
        $map = [];
        foreach (array_keys($attributes) as $attributeName) {
            $map[$attributeName] = strtolower($attributeName);
        }

        return $map;
    }
}
