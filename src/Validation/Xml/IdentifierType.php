<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Xml;

use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Validation\Base\Type;
use Lsa\Xml\Utils\Validation\Validators\RegexValidator;

/**
 * In CSS, identifiers (including element names, classes, and IDs in selectors) can contain only the
 * characters [a-zA-Z0-9] and ISO 10646 characters U+0080 and higher, plus the hyphen (-) and the
 * underscore (_); they cannot start with a digit, two hyphens, or a hyphen followed by a digit. Identifiers
 * can also contain escaped characters and any ISO 10646 character as a numeric code (see next item). For
 * instance, the identifier "B&W?" may be written as "B\&W\?" or "B\26 W\3F".
 *
 * Note that Unicode is code-by-code equivalent to ISO 10646 (see [UNICODE] and [ISO10646]).
 *
 * ----
 * All levels of CSS — level 1, level 2, and any future levels — use the same core syntax. This allows
 * UAs to parse (though not completely understand) style sheets written in levels of CSS that did not exist
 * at the time the UAs were created. Designers can use this feature to create style sheets that work with
 * older user agents, while also exercising the possibilities of the latest levels of CSS.
 *
 * At the lexical level, CSS style sheets consist of a sequence of tokens. The list of tokens for CSS is as
 * follows. The definitions use Lex-style regular expressions. Octal codes refer to ISO 10646 ([ISO10646]).
 * As in Lex, in case of multiple matches, the longest match determines the token.
 * - IDENT  {ident}
 * - ATKEYWORD  @{ident}
 * - STRING  {string}
 * - BAD_STRING  {badstring}
 * - BAD_URI  {baduri}
 * - BAD_COMMENT  {badcomment}
 * - HASH  #{name}
 * - NUMBER  {num}
 * - PERCENTAGE  {num}%
 * - DIMENSION  {num}{ident}
 * - URI  {U}{R}{L}\({w}{string}{w}\)|{U}{R}{L}\({w}([!#$%&*-\[\]-~]|{nonascii}|{escape})*{w}\)
 * - UNICODE-RANGE  u\+[?]{1,6}|u\+[0-9a-f]{1}[?]{0,5}|u\+[0-9a-f]{2}[?]{0,4}|u\+[0-9a-f]{3}[?]{0,3}|u\+[0-9a-f]{4}[?]{0,2}|u\+[0-9a-f]{5}[?]{0,1}|u\+[0-9a-f]{6}|u\+[0-9a-f]{1,6}-[0-9a-f]{1,6}
 * CDO  <!--
 * CDC  -->
 * :  :
 * ;  ;
 * {  \{
 * }  \}
 * (  \(
 * )  \)
 * [  \[
 * ]  \]
 * S  [ \t\r\n\f]+
 * COMMENT  \/\*[^*]*\*+([^/*][^*]*\*+)*\/
 * FUNCTION  {ident}\(
 * INCLUDES  ~=
 * DASHMATCH  |=
 * DELIM  any other character not matched by the above rules, and neither a single nor a double quote
 *
 * The macros in curly braces ({}) above are defined as follows:
 * - ident  [-]?{nmstart}{nmchar}*
 * - name  {nmchar}+
 * - nmstart  [_a-z]|{nonascii}|{escape}
 * - nonascii [^\0-\177]
 * - unicode  \\[0-9a-f]{1,6}(\r\n|[ \n\r\t\f])?
 * - escape  {unicode}|\\[^\n\r\f0-9a-f]
 * - nmchar  [_a-z0-9-]|{nonascii}|{escape}
 * - num  [+-]?([0-9]+|[0-9]*\.[0-9]+)(e[+-]?[0-9]+)?
 * - string  {string1}|{string2}
 * - string1  \"([^\n\r\f\\"]|\\{nl}|{escape})*\"
 * - string2  \'([^\n\r\f\\']|\\{nl}|{escape})*\'
 * - badstring  {badstring1}|{badstring2}
 * - badstring1  \"([^\n\r\f\\"]|\\{nl}|{escape})*\\?
 * - badstring2  \'([^\n\r\f\\']|\\{nl}|{escape})*\\?
 * - badcomment  {badcomment1}|{badcomment2}
 * - badcomment1  \/\*[^*]*\*+([^/*][^*]*\*+)*
 * - badcomment2  \/\*[^*]*(\*+[^/*][^*]*)*
 * - baduri  {baduri1}|{baduri2}|{baduri3}
 * - baduri1  {U}{R}{L}\({w}([!#$%&*-~]|{nonascii}|{escape})*{w}
 * - baduri2  {U}{R}{L}\({w}{string}{w}
 * - baduri3  {U}{R}{L}\({w}{badstring}
 * - nl  \n|\r\n|\r|\f
 * - w  [ \t\r\n\f]*
 * - L  l|\\0{0,4}(4c|6c)(\r\n|[ \t\r\n\f])?|\\l
 * - R  r|\\0{0,4}(52|72)(\r\n|[ \t\r\n\f])?|\\r
 * - U  u|\\0{0,4}(55|75)(\r\n|[ \t\r\n\f])?|\\u
 *
 * For example, the rule of the longest match means that "red-->" is tokenized as the IDENT "red--"
 * followed by the DELIM ">", rather than as an IDENT followed by a CDC.
 *
 * @link https://www.w3.org/TR/CSS22/syndata.html#tokenization
 */
class IdentifierType extends Type implements Validator
{
    public const INVALID_START = '(?!-?\d)(?!--)';

    public const IDENTIFIER_START_CHAR = '(?:[a-zA-Z_-]|[^\x00-\x7F]|\\[0-9a-fA-F]{1,6}\s?|\\[^0-9a-fA-F])';

    public const IDENTIFIER_CHAR = '(?:[a-zA-Z0-9_-]|[^\x00-\x7F]|\\[0-9a-fA-F]{1,6}\s?|\\[^0-9a-fA-F])';

    public const IDENTIFIER = self::INVALID_START.self::IDENTIFIER_START_CHAR.self::IDENTIFIER_CHAR.'*';

    public function getValidator(): Validator
    {
        return $this->cache(new RegexValidator(
            self::INVALID_START.self::IDENTIFIER,
            'u'
        ));
    }
}
