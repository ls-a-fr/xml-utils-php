# XML Utils

Cette bibliothèque offre une interface simple et lisible pour créer et manipuler du XML en toute simplicité. Comme de nombreuses autres bibliothèques, elle prend en charge la structure XML, mais propose une API compréhensible.

Exemple de code :
```php
<?php
// Une balise footnote
class FootnoteTag extends Tag implements HasDefinition
{
    // Cette méthode vous permet de déterminer (si nécessaire) le nom de la balise
    public function getTagName(): string
    {
        return 'footnote';
    }

    // Cette méthode permet de définir un ensemble de contraintes strictes sur la balise
    public function asDefinition(): Definition
    {
        return (new Definition())
            // Eléments autorisés dans cette balise : `<inline>` et `<footnote-body>`
            ->sequence(new Sequence(
                new Element(Inline::class),
                new Element(FootnoteBody::class),
            ))
            // Attributs autorisés
            ->allows([
                // Un groupe d'attributs
                AccessibilityProperties::class,
                // Une propriété
                Id::class,
                // Un attribut typé (TypedAttribute)
                new TypedAttribute('margin', MarginType::class)
            ])
            // Attributs hérités
            ->inheritables([
                // Un autre groupe d'attributs
                InheritedProperties::class,
            ]);
    }
}
```

## Fonctionnalités

Cette bibliothèque, comme beaucoup d’autres, prend en charge :

- Les balises (`<block></block>`), les balises vides (`<img/>`) et les nœuds de texte (`contenu`)
- Les attributs (`attribut="valeur"`)
- La génération de document XML à partir d’une structure d’objets

Mais elle prend également en charge :

- Les propriétés, c’est-à-dire un attribut avec un type, pouvant être validé par un validateur
- Les collections, permettant d’ajouter, de supprimer, de filtrer, d'utiliser *map* ou *reduce* sur des balises et des propriétés
- Les groupes d’attributs (*AttributeGroup*), basés sur la structure XSD
- XPath pour les requêtes complexes
- La validation des balises (quelles balises sont autorisées à quel endroit), basée sur la structure XSD
- Les attributs abrégés, par exemple `margin` pour `margin-top`, `margin-left`, `margin-right` et `margin-bottom`
- Les attributs composés, par exemple `space-before.minimum="0pt" space-before.maximum="10pt"`
- Les attributs hérités, pour accéder aux valeurs via la hiérarchie
- Les validations complexes pour les propriétés : Union, Cumulative, CumulativeOrdered, Intersect, InverseRegex, etc.
- La compatibilité XSD

Pour chaque structure, vous pouvez utiliser la méthode `validate` afin de vérifier si la structure générée est correcte.

## Pourquoi ?

Premièrement, pourquoi pas ? Nous n'avons pas trouvé de bibliothèque fournissant tous les éléments dont nous avions besoin, spécifiquement la compatibilité XSD et les validations complexes : cela manquait sur composer et peut-être que notre bibliothèque vous sera également utile.  
Ensuite, pour l'histoire, ce package est utilisé dans la [bibliothèque XSL-Core](https://github.com/ls-a-fr/xsl-core-php), pour gérer les différents appels de fonctions dans des attributs XML.

## Installation

Ce package est disponible sur Composer. Pour l'installer :
```sh
composer require ls-a/xml-utils
```

## Journal des modifications

Veuillez consulter le fichier [CHANGELOG](CHANGELOG.md) pour voir les dernières modifications.

## Support

Nous mettons du coeur à l'ouvrage pour proposer des produits de qualité et accessibles à toutes et tous. Si vous aimez notre travail, n'hésitez pas à faire appel à nous pour votre prochain projet !  

## Contributions

Les contributions sont régies par le fichier [CONTRIBUTING](https://github.com/ls-a-fr/.github/CONTRIBUTING.md).  
Les tests sont manquants pour le moment dans cette bibliothèque, et nous n'avons pas beaucoup de temps en ce moment pour les rédiger. **Cependant** cette bibliothèque est considérée comme stable, puisqu'elle est utilisée par plusieurs autres modules chez nous possédant des milliers de tests.

## Sécurité

Si vous avez déniché un bug ou une faille, merci de nous contacter par mail à [mailto:contact@ls-a.fr](contact@ls-a.fr) en lieu et place d'une issue, pour respecter la sécurité des autres usagers.


## Crédits

- Renaud Berthier

## Licence

Code déposé sous licence MIT. Rendez-vous sur le fichier LICENSE pour davantage d'informations.