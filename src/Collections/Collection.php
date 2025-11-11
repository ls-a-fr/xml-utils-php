<?php

declare(strict_types=1);

namespace Lsa\Xml\Utils\Collections;

use Countable;
use IteratorAggregate;
use Lsa\Xml\Utils\Exceptions\InvalidCollectionOperationException;

/**
 * A base class to implements Collections, with a somehow generic system. This class will become obsolete
 * when Generics lands in PHP, can't wait!
 *
 * @phpstan-consistent-constructor
 *
 * @template T
 *
 * @implements IteratorAggregate<string|int, T>
 */
abstract class Collection implements Countable, IteratorAggregate
{
    /**
     * Data contained in this Collection
     *
     * @var T[]
     */
    protected array $data = [];

    /**
     * Creates a new Collection
     *
     * @param  T[]  $elements
     */
    public function __construct(array $elements = [])
    {
        $this->data = $elements;
    }

    /**
     * Returns an iterator for parameters.
     *
     * @return \ArrayIterator<string|int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Returns the number of elements in this collection.
     */
    public function count(): int
    {
        return \count($this->data);
    }

    /**
     * Empties the collection.
     */
    public function clean(): void
    {
        $this->data = [];
    }

    /**
     * Adds all supplied elements in the current Collection.
     *
     * @param  T[]|Collection<T>  $elements  Elements to add
     */
    public function addAll($elements): static
    {
        foreach ($elements as $element) {
            $this->add($element);
        }

        return $this;
    }

    /**
     * Add the supplied element in the current Collection.
     *
     * @param  T  $element  Element to add
     */
    public function add($element): static
    {
        $this->data[] = $element;

        return $this;
    }

    /**
     * Removes the supplied element from the current Collection.
     *
     * @param  T  $element  Element to add
     */
    public function remove($element): static
    {
        try {
            $key = $this->getIndex($element);
        } catch (InvalidCollectionOperationException) {
            $key = $this->find($element);
        }
        unset($this->data[$key]);

        return $this;
    }

    /**
     * Gets an element index. Will work only on index-based collections.
     *
     * @param  T  $element
     * @return ?int Index if found, null otherwise.
     *
     * @throws InvalidCollectionOperationException If this is an associative collection. Use the `find` method
     */
    public function getIndex($element): ?int
    {
        $index = $this->doFind($element);
        if (\is_string($index) === true) {
            throw new InvalidCollectionOperationException(
                'Cannot use getIndex on a string-based Collection. Use `find`.'
            );
        }

        return $index;
    }

    /**
     * Actual find operation.
     *
     * @param  T  $element  Element to find
     * @return int|string|null Key if found, null otherwise.
     */
    private function doFind($element): int|string|null
    {
        $result = \array_search($element, $this->data, true);
        if ($result !== false) {
            return $result;
        }

        return null;
    }

    /**
     * Finds the supplied element index from the current Collection.
     *
     * @param  T  $element  Element to add
     * @return ?string Key if found, null otherwise.
     *
     * @throws InvalidCollectionOperationException Bad usage of this function
     */
    public function find($element): ?string
    {
        if (\array_is_list($this->data) === true) {
            throw new InvalidCollectionOperationException(
                'Cannot use find on an index-based Collection. Use `getIndex`.'
            );
        }
        $index = $this->doFind($element);
        if (\is_int($index) === true) {
            throw new InvalidCollectionOperationException(
                'Cannot use find on an index-based Collection. Use `getIndex`.'
            );
        }

        return $index;
    }

    /**
     * Checks if the supplied element is inside the current Collection.
     *
     * @param  int|string|T  $element  Searched element
     * @return bool True if found, false otherwise.
     */
    public function has($element): bool
    {
        $isKey = \is_string($element) === true || is_int($element) === true;
        foreach ($this->data as $k => $v) {
            if (
                ($isKey === true && $k === $element)
                || $v === $element
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filters the current Collection.
     *
     * @param  callable(T):bool  $fn  Predicate to execute
     *
     * @see https://php.net/manual/en/function.array-filter.php
     */
    public function filter(callable $fn): static
    {
        /**
         * Function array_filter will return elements of type T
         *
         * @phpstan-ignore return.type
         */
        return new static(array_filter($this->data, $fn));
    }

    /**
     * Maps the current Collection.
     *
     * @param  callable(T):mixed  $fn  Predicate to execute
     *
     * @see https://php.net/manual/en/function.array-map.php
     */
    public function map(callable $fn): MixedCollection
    {
        return new MixedCollection(array_map($fn, $this->data));
    }

    /**
     * Reduces the current Collection.
     *
     * // phpcs:disable Generic.Commenting.DocComment.ParamNotFirst
     *
     * @template U of mixed
     *
     * @param  callable(U, T):U  $fn  Predicate to execute
     * @param  U  $defaultValue  Default value if Collection is empty. Default null.
     * @return U Result from this reduction
     *
     * @see https://php.net/manual/en/function.array-map.php
     */
    public function reduce(callable $fn, mixed $defaultValue = null): mixed
    {
        return array_reduce($this->data, $fn, $defaultValue);
    }

    /**
     * Returns this Collection as an array.
     *
     * @return T[]
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Checks if this Collection is empty.
     *
     * @return bool True if this Collection is empty, false otherwise.
     */
    public function isEmpty(): bool
    {
        return count($this->data) === 0;
    }

    /**
     * Creates a new Collection based on an array of elements.
     *
     * @param  T[]  $elements  Elements to add
     * @return static New instance of this Collection
     */
    public static function fromArray(array $elements): static
    {
        /**
         * Function array_filter will return elements of type T
         *
         * @phpstan-ignore return.type
         */
        return new static($elements);
    }

    /**
     * Returns the first element of this Collection.
     *
     * @return ?T First element found, or null if this Collection is empty.
     */
    public function first()
    {
        if ($this->isEmpty() === true) {
            return null;
        }

        $firstKey = array_keys($this->data)[0];

        return $this->data[$firstKey];
    }

    /**
     * Merges an existing Collection
     *
     * @param  Collection<T>  $collection  The collection
     */
    public function merge(Collection $collection): static
    {
        foreach ($collection->toArray() as $element) {
            $this->add($element);
        }

        return $this;
    }
}
