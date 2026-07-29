<?php
namespace Rehike\Network;

use ArrayAccess;
use Iterator;

use function reset;
use function current;
use function key;
use function next;

/**
 * Implements an array for accessing HTTP headers.
 * 
 * This is very similar to the Controller v2 RequestMetadata structure
 * used by the Rehike project, except this is also iterable using foreach
 * loops.
 * 
 * @todo Coalesce both this and RequestMetadata into a single common class?
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 */
class ResponseHeaders implements ArrayAccess, Iterator
{
    /**
     * Bound array that stores definitions.
     * 
     * @var mixed[]
     */
    private array $boundArray = [];

    public function __construct(array $baseArray)
    {
        $this->boundArray = $baseArray;
    }

    /**
     * Attempt to get a property on the class if it is readable.
     */
    public function __get(string $var): mixed
    {
        // Headers are case-insensitive.
        $lowercaseName = strtolower($var);

        // Converted from camelCase to hyphen-case
        $hyphenCaseName = strtolower(
            preg_replace("/(?<!^)[A-Z]/", "-$0", $var)
        );

        // And to snake_case
        $snake_case_name = str_replace("_", "-", $var);

        // Then check if the raw name is accessible in the object
        if (isset($this->boundArray[$lowercaseName]))
        {
            return $this->boundArray[$lowercaseName];
        }
        // Otherwise, check if the camelCase name is accessible
        // in the object.
        else if (isset($this->boundArray[$hyphenCaseName]))
        {
            return $this->boundArray[$hyphenCaseName];
        }
        // If that's not the case, check if it's snake_case
        else if (isset($this->boundArray[$snake_case_name]))
        {
            return $this->boundArray[$snake_case_name];
        }
        // Finally, if none of those are set, return an empty string
        else
        {
            return "";
        }
    }

    public function __isset(string $var): bool
    {
        return "" != $this->__get($var);
    }

    public function __set(string $name, mixed $value)
    {
        $this->offsetSet(null, null); // inherit warning
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->boundArray[$offset]);
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        trigger_error(__CLASS__ . "::\$boundArray is read only.", E_USER_WARNING);
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        $this->offsetSet(null, null); // inherit warning
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return isset($this->boundArray[$offset])
            ? $this->boundArray[$offset]
            : null
        ;
    }

    #[\Override]
    public function rewind(): void
    {
        reset($this->boundArray);
    }

    #[\Override]
    public function current(): mixed
    {
        return current($this->boundArray);
    }

    #[\Override]
    public function key(): mixed
    {
        return key($this->boundArray);
    }

    #[\Override]
    public function next(): void
    {
        next($this->boundArray);
    }

    #[\Override]
    public function valid(): bool
    {
        return key($this->boundArray) !== null;
    }
}