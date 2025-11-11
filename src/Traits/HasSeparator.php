<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Traits;

/**
 * Values are usually separated as spaces in an attribute value.
 * A Type having this trait can handle several values, all separated by the specified separator.
 */
trait HasSeparator
{
    /**
     * The character used as separator in an attribute value
     */
    public string $separator;

    /**
     * Whether this validator should trim strings or not
     */
    public bool $shouldTrim = false;

    /**
     * Sets the separator character used as separator in an attribute value
     *
     * @param  string  $separator  The character
     */
    public function separator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * Specifies exploded parts should be trimmed. This setting does not magically happens, it must be
     * implemented on validators.
     */
    public function shouldTrim(): static
    {
        $this->shouldTrim = true;

        return $this;
    }

    /**
     * Use `explode` function to separate a string into chunks. Does a specific space separator treatment,
     * as it removes empty chunks.
     *
     * Example: The string "a  b c     d e" will give `['a', 'b', 'c', 'd', 'e']` instead of
     * `['a','','b','c','','','','','d','e']`
     *
     * Without a separator, make every character a chunk.
     *
     * @param  string  $value  The value to split into chunks
     * @return list<string> The exploded values
     */
    public function separate(string $value): array
    {
        if (str_contains($value, '(') === false) {
            return $this->separateWithoutParse($value);
        }

        return $this->separateWithParse($value);
    }

    /**
     * Separates a string "easily", without any parsing.
     *
     * @param  string  $value  The value to split into chunks
     * @return list<string> The exploded values
     */
    protected function separateWithoutParse(string $value): array
    {
        if (isset($this->separator) === false || $this->separator === '') {
            return str_split($value);
        }
        if ($this->separator === ' ' && $this->shouldTrim === true) {
            return array_values(array_filter(explode($this->separator, $value), fn ($v) => $v !== ''));
        }

        return explode($this->separator, $value);
    }

    /**
     * Separates a string with separator parsing, meaning parentheses handling.
     *
     * @param  string  $value  The value to split into chunks
     * @return list<string> The exploded values
     */
    protected function separateWithParse(string $value): array
    {
        $openParenthesis = 0;
        $chunks = [];
        $currentChunk = '';
        $size = strlen($value);
        for ($i = 0; $i < $size; $i++) {
            if ($value[$i] === $this->separator && $openParenthesis === 0) {
                $chunks[] = $currentChunk;
                $currentChunk = '';

                continue;
            }

            $currentChunk .= $value[$i];
            if ($value[$i] === '(') {
                $openParenthesis++;

                continue;
            }
            if ($value[$i] === ')') {
                $openParenthesis--;

                continue;
            }
        }
        $chunks[] = $currentChunk;

        return $chunks;
    }
}
