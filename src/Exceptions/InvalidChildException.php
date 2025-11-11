<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Exceptions;

use Exception;

/**
 * Exception caused by adding a child to an `\Lsa\Xml\Utils\Xml\Base\EmptyTag` object
 */
class InvalidChildException extends XmlUtilsException {}
