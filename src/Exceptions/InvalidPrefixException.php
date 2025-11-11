<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Exceptions;

use Exception;

/**
 * Exception caused by specifying an incorrect prefix to a `\Lsa\Xml\Utils\Traits\ProvidesTag` object
 */
class InvalidPrefixException extends XmlUtilsException {}
