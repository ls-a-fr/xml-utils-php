<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Exceptions;

use Exception;

/**
 * Exception caused by calling an XPath query on a non-tag object.
 */
class InvalidXpathException extends XmlUtilsException {}
