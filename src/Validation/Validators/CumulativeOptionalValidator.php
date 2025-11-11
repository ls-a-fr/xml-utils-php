<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Validation\Validators;

use Lsa\Xml\Utils\Contracts\MultiValidator;
use Lsa\Xml\Utils\Contracts\Validator;
use Lsa\Xml\Utils\Xml\Base\EmptyTag;
use Lsa\Xml\Utils\Xml\Base\Tag;

/**
 * A CumulativeOrderedValidator allows to validate several strings set in an attribute value.
 * This validator will split the value based on a separator, then check every chunk with any
 * Validator.
 */
class CumulativeOptionalValidator extends CumulativeValidator implements MultiValidator, Validator
{
    /**
     * This validator is optional
     */
    public const OPTIONAL = 0;

    /**
     * This validator is mandatory
     */
    public const MANDATORY = 1;

    /**
     * At least one validator must be triggered and validated
     */
    public const AT_LEAST_ONE = 2;

    /**
     * No validator can be triggered
     */
    public const CAN_BE_NONE = 4;

    /**
     * Ordered validators
     *
     * @var list<array{int, \Lsa\Xml\Utils\Contracts\Validator}>
     */
    private array $validatorsData;

    /**
     * Selected mode. Must comply with constants (see above)
     */
    private int $mode;

    /**
     * Creates a new CumulativeOptionalValidator
     *
     * @param  list<array{int, \Lsa\Xml\Utils\Contracts\Validator}>  $validatorsData  Ordered validators
     * @param  int  $mode  Current CumulativeOptionalValidator mode
     */
    public function __construct(array $validatorsData, int $mode = self::AT_LEAST_ONE)
    {
        $validators = [];
        foreach ($validatorsData as $row) {
            $validators[] = $row[1];
        }

        $this->validatorsData = $validatorsData;
        $this->mode = $mode;

        parent::__construct(...$validators);
    }

    /**
     * Splits the string in several chunks, based on the specified separator. Then validate every chunk
     * with any validator. If a validation succeeds, the next value is checked.
     */
    public function validateWithContext(string $value, ?Tag $root, ?EmptyTag $current): bool
    {
        $parts = $this->separate($value);
        $validators = [...$this->validatorsData];
        foreach ($parts as $part) {
            if ($this->shouldTrim === true) {
                $part = trim($part);
            }
            if (empty($validators) === true) {
                return false;
            }
            foreach ($validators as $validatorIndex => $validatorData) {
                if ($this->validateSingleValidator($validatorData[1], $part, $root, $current) === true) {
                    unset($validators[$validatorIndex]);

                    continue 2;
                }
            }

            return false;
        }

        if (empty($validators) === true) {
            return true;
        }

        foreach ($validators as $row) {
            if ($row[0] === self::MANDATORY) {
                return false;
            }
        }

        if ($this->mode === self::AT_LEAST_ONE && count($validators) === count($this->validators)) {
            return false;
        }

        return true;
    }
}
