# XML Utils

This documentation is also available in these languages:
- [Français](docs/LISEZMOI.md)

This library provides a simple and readable interface to create and manipulate XML with ease. As many libraries out there, it supports XML structure, but with a very comprehensive API.

Code sample:
```php
<?php
// A footnote tag
class FootnoteTag extends Tag implements HasDefinition
{
    // This method allows you to customize tag name, if you wish to
    public function getTagName(): string
    {
        return 'footnote';
    }

    // This method allows you to define a strict constraint on this tag
    public function asDefinition(): Definition
    {
        return (new Definition())
            // Allowed elements in this tag: `<inline>` and `<footnote-body>`
            ->sequence(new Sequence(
                new Element(Inline::class),
                new Element(FootnoteBody::class),
            ))
            // Allowed attributes
            ->allows([
                // An attribute group
                AccessibilityProperties::class,
                // A property
                Id::class,
                // A typed attribute
                new TypedAttribute('margin', MarginType::class)
            ])
            // Inherited attributes
            ->inheritables([
                // Another attribute group
                InheritedProperties::class,
            ]);
    }
}
```

## Features

This library, as many others, supports:
- Tags (`<block></block>`), EmptyTags (`<img/>`) and TextNodes (`content`)
- Attributes (`key="value"`)
- XML generation out of object structure

But it also supports:
- Properties, meaning an attribute with a Type, that can be validated with a Validator
- Collections, to add/remove/filter/map/reduce Tags and Properties
- AttributeGroup, based on XSD structure
- XPath for complex queries
- Validation for tags (which tag is allowed where), based on XSD structure
- Shorthand attributes, eg `margin` for `margin-top`, `margin-left`, `margin-right` and `margin-bottom`
- Compound attributes, eg `space-before.minimum="0pt" space-before.maximum="10pt`
- Inherited attributes to access values from traversal
- Complex validations for Properties: Union, Cumulative, CumulativeOrdered, Intersect, InverseRegex, ...
- XSD compatibility

For each structure, you can use the `validate` method to check whether the generated structure is correct.

## Why?

First, because why not? We could not find an XML library that provides every aspect we needed, especially conformance with XSD and complex validations: we felt it was missing and maybe you would also like it.  
Next, for a little background, this package is used in [XSL-Core package](https://github.com/ls-a-fr/xsl-core-php), to handle various XML function calls in attributes.

## Installation

This library will (soon) be available on Composer. Install it with:
```sh
composer require ls-a/xml-utils
```

## Changelog

Please refer to the [CHANGELOG](CHANGELOG.md) file to see the latest changes.

## Support

We put our heart into delivering high-quality products that are accessible to everyone. If you like our work, don’t hesitate to reach out to us for your next project!

## Contributing

Contributions are governed by the [CONTRIBUTING](https://github.com/ls-a-fr/.github/CONTRIBUTING.md) file.  
Tests are still missing and we don't have much time to write them now, **however** this package is considered stable, as it is used by several other packages in our end that have thousands of tests.

## Security

If you’ve found a bug or vulnerability, please contact us by email at [contact@ls-a.fr](mailto:contact@ls-a.fr) instead of opening an issue, in order to protect the security of other users.

## Credits

- Renaud Berthier

## License

The MIT License (MIT). Please see License File for more information.