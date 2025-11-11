<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Xml;

use Lsa\Xml\Utils\Collections\AttributeCollection;
use Lsa\Xml\Utils\Collections\NodeCollection;
use Lsa\Xml\Utils\Exceptions\InvalidNameException;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * Represents any tag you wish to create. You must supply a name.
 */
class CustomTag extends Tag
{
    /**
     * Stored the supplied tag name
     */
    private readonly string $suppliedTag;

    /**
     * Gets this tag name
     */
    public function getTagName(): string
    {
        return $this->suppliedTag;
    }

    /**
     * Creates a new CustomTag
     *
     * @param  string  $tag  The tag name
     * @param  AttributeCollection  $attributes  Tag attributes
     * @param  NodeCollection  $children  Tag children
     *
     * @throws InvalidNameException If name is invalid
     */
    public function __construct(
        string $tag,
        $attributes = new AttributeCollection(),
        $children = new NodeCollection()
        // phpcs:disable Generic.Functions.OpeningFunctionBraceBsdAllman.BraceOnSameLine
    ) {
        // Check for tag validness
        if (self::isValidName($tag) === false) {
            throw new InvalidNameException('Invalid tag name');
        }
        $this->suppliedTag = $tag;

        parent::__construct($attributes, $children);
    }
}
