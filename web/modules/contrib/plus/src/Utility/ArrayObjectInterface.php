<?php

namespace Drupal\plus\Utility;

/**
 * Further extends SPL ArrayObject with a little more functionality.
 *
 * @ingroup utility
 */
interface ArrayObjectInterface extends \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Serializable {

  /**
   * Creates a new unreferenced StaticArray instance.
   *
   * @param mixed ...
   *   An array or an Object.
   *
   * @return static
   */
  public static function create(...$arguments);

  /**
   * Creates a new referenced StaticArray instance.
   *
   * @param mixed ...
   *   An array or an Object, passed by reference.
   *
   * @return static
   */
  public static function reference(&...$arguments);

  /**
   * Appends a value.
   *
   * @param mixed $value
   *   The value to append.
   * @param string|int $key
   *   The key of the value to append. If already set, the value will not be
   *   appended.
   *
   * @return static
   */
  public function append($value, $key = NULL);

  /**
   * Sort the entries by value.
   *
   * @return static
   */
  public function asort();

  /**
   * Statically clones the instance.
   *
   * If the object being cloned has any stored values that are referenced,
   * these references will not be transferred to the new cloned object.
   *
   * @return static
   *
   * @todo rename to "clone" when only PHP 7 and above is supported.
   */
  public function copy();

  /**
   * Determines if a value is equal to what is currently set.
   *
   * Note: this is a strict check, so even if the objects/arrays contain the
   * same key/value pairs, if they're in a different order, this will fail.
   * If you need to compare if they're similar, use that method.
   *
   * @param \Drupal\plus\Utility\StaticArrayInterface|array|mixed $value
   *   The value to compare.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\plus\Utility\StaticArrayInterface::similar()
   */
  public function equals($value);

  /**
   * Returns whether the requested key exists.
   *
   * @param string $key
   *   The key to check.
   * @param bool $check_key
   *   Flag indicating whether to check if the $key exists or if a value is set.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function exists($key, $check_key = TRUE);

  /**
   * Attempts to find an item by a key or pattern.
   *
   * @param string $key
   *   A key name or pattern matching a key.
   * @param mixed $default
   *   The default value to use if not found.
   *
   * @return mixed|null
   *   The found value or $default.
   */
  public function find($key, $default = NULL);

  /**
   * Finds items matching a specific pattern.
   *
   * @param string $pattern
   *   A regular expression pattern.
   *
   * @return array
   *   An array of values
   */
  public function &findAll($pattern);

  /**
   * Finds the first occurrence of a pattern.
   *
   * @param string $pattern
   *   A regular expression pattern.
   * @param mixed $default
   *   The default value to use if not found.
   *
   * @return mixed|null
   *   The found value or $default.
   */
  public function findFirst($pattern, $default = NULL);

  /**
   * Finds the last occurrence of a pattern.
   *
   * @param string $pattern
   *   A regular expression pattern.
   * @param mixed $default
   *   The default value to use if not found.
   *
   * @return mixed|null
   *   The found value or $default.
   */
  public function findLast($pattern, $default = NULL);

  /**
   * Iterates over each value in the array passing them to the callback.
   *
   * If the callback returns TRUE, the current value from the array is
   * returned into the result array. Array keys are preserved.
   *
   * @param callable|null $callback
   *   (optional) The callback function to use. If no callback is provided, all
   *   converted values equal to FALSE will be removed.
   * @param int $flag
   *   (optional) Flag determining what arguments are sent to $callback.
   *
   * @return static
   *
   * @see \array_filter()
   */
  public function filter($callback = NULL, $flag = 0);

  /**
   * Flattens a multidimensional array.
   *
   * @param bool $shallow
   *   Flag indicating whether the array will only flatten a single level.
   *
   * @return static
   */
  public function flatten($shallow = FALSE);

  /**
   * Retrieves the value for a specified key.
   *
   * @param string $key
   *   The key to retrieve.
   * @param mixed $default
   *   (optional) The default value to return if $key is not set, defaults to
   *   NULL.
   * @param bool $set_if_no_existence
   *   Flag indicating whether to set the value to the default value if it
   *   doesn't yet exist. If FALSE, it will only return defaultValue, not set
   *   it.
   *
   * @return mixed
   *   The value for the specified key or the value of $default if not set.
   */
  public function &get($key, $default = NULL, $set_if_no_existence = TRUE);

  /**
   * Creates a copy of the StaticArray.
   *
   * @return array
   *   A static copy of the array. When the StaticArray refers to an object
   *   an array of the public properties of that object will be returned.
   */
  public function getArrayCopy();

  /**
   * Indicates whether the StaticArray has changed.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasChanged();

  /**
   * Indicates whether the StaticArray is an associative array.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isAssociative();

  /**
   * Indicates whether the StaticArray is empty.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isEmpty();

  /**
   * Indicates whether the StaticArray is an indexed array.
   *
   * Note: this will return TRUE even if the array index is not sequential. If
   * you need to determine whether the array is indexed and sequential, use
   * StaticArrayInterface::isSequential() instead.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\plus\Utility\StaticArrayInterface::isSequential()
   */
  public function isIndexed();

  /**
   * Indicates whether the StaticArray is a sequential indexed array.
   *
   * Note: this will return TRUE only if the array index is sequential. If
   * you need to determine whether the array is indexed, regardless of
   * sequential order, use StaticArrayInterface::isIndexed() instead.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\plus\Utility\StaticArrayInterface::isIndexed()
   */
  public function isSequential();

  /**
   * Retrieves the StaticArray keys.
   *
   * @return array
   *   The StaticArray keys.
   */
  public function keys();

  /**
   * Sort the entries by key.
   *
   * @return static
   */
  public function ksort();

  /**
   * Applies the callback to the elements of the array.
   *
   * @param callable $callback
   *   Callback function to run for each element in each array.
   *
   * @return static
   *
   * @see \array_map()
   */
  public function map(callable $callback);

  /**
   * Merge in other value(s).
   *
   * @param array|object ...
   *   One or more associative key/value pair arrays or \StaticArray instances.
   *
   * @return static
   */
  public function merge(&...$arguments);

  /**
   * Merge in other values, recursively.
   *
   * Note: this will destroy any references, use with caution.
   *
   * @param array|object ...
   *   One or more associative key/value pair arrays or \StaticArray instances.
   *
   * @return static
   */
  public function mergeDeep(&...$arguments);

  /**
   * Sort an array using a case insensitive "natural order" algorithm.
   *
   * @return static
   */
  public function natcasesort();

  /**
   * Sort entries using a "natural order" algorithm.
   *
   * @return static
   */
  public function natsort();

  /**
   * Prepends a value.
   *
   * @param mixed $value
   *   The value to prepend, passed by reference.
   * @param string|int $key
   *   The key of the value to set.
   *
   * @return static
   */
  public function prepend(&$value, $key = NULL);

  /**
   * Removes the value for the specified key.
   *
   * @param string|string[] ...
   *   Keys to remove from the StaticArray storage.
   *
   * @return static
   */
  public function remove(...$keys);

  /**
   * Replaces the StaticArray storage with new value.
   *
   * @param array $value
   *   The value used to replace the StaticArray storage.
   * @param array $previous
   *   (optional) A parameter, passed by reference, used to capture any
   *   previously set StaticArray storage.
   *
   * @return static
   */
  public function replace(array &$value = [], array &$previous = []);

  /**
   * Sets the value at the specified index to value.
   *
   * @param string $key
   *   The key to set.
   * @param mixed $value
   *   The new value for $key.
   *
   * @return static
   */
  public function set($key, $value = NULL);

  /**
   * Determines if a value is similar to what is currently set.
   *
   * Note: this is not a strict check. If the objects/arrays contain the
   * same key/value pairs, regardless of order, this will pass. If you need to
   * compare if they're equal (same positions), use that method.
   *
   * @param \Drupal\plus\Utility\StaticArrayInterface|array|mixed $value
   *   The value to compare.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\plus\Utility\StaticArrayInterface::equals()
   */
  public function similar($value);

  /**
   * Sort with a user-defined comparison function, maintaining key association.
   *
   * @param callable|mixed $callback
   *   The $callback function should accept two parameters which will be
   *   filled by pairs of entries. The comparison function must return an
   *   integer less than, equal to, or greater than zero if the first argument
   *   is considered to be respectively less than, equal to, or greater than
   *   the second.
   *
   * @return static
   */
  public function uasort($callback);

  /**
   * Sort the entries by keys using a user-defined comparison function.
   *
   * @param callback|mixed $callback
   *   The callback comparison function. The $callback function should accept
   *   two parameters which will be filled by pairs of entry keys. The
   *   comparison function must return an integer less than, equal to, or
   *   greater than zero if the first argument is considered to be respectively
   *   less than, equal to, or greater than the second.
   *
   * @return static
   */
  public function uksort($callback);

  /**
   * Removes duplicate values from an array.
   *
   * @param int $sort_flags
   *   (optional) The flag(s) to pass to array_unique().
   *
   * @return static
   *
   * @see \array_unique()
   */
  public function unique($sort_flags = SORT_STRING);

  /**
   * Retrieves the StaticArray storage.
   *
   * @return mixed
   *   The StaticArray storage, returned by reference.
   */
  public function &value();

  /**
   * Apply a user function to every member of the array.
   *
   * @param callable $callback
   *   Callback function to run for each element in each array.
   * @param bool $recursive
   *   Flag indicating whether walk should be recursive.
   * @param mixed $user_data
   *   If the optional userdata parameter is supplied, it will be passed as
   *   the third parameter to the callback.
   *
   * @return static
   *
   * @see \array_walk()
   * @see \array_walk_recursive()
   */
  public function walk(callable $callback, $recursive = FALSE, $user_data = NULL);

}
