<?php

namespace Drupal\plus\Utility;

/**
 * Further extends SPL ArrayObject with a little more functionality.
 *
 * @ingroup utility
 */
class ArrayObject extends \ArrayObject implements ArrayObjectInterface {

  // The following properties are purposefully prefixed with __ so they don't
  // clash with any stored property names. To ensure tests pass, Drupal coding
  // standards must be ignored here.
  // @codingStandardsIgnoreStart

  /**
   * Flag indicating whether the object has changed.
   *
   * @var bool
   */
  protected $__changed = FALSE;

  /**
   * The currently set flag(s).
   *
   * @var int
   */
  protected $__flags;

  /**
   * Flag indicating whether the object is an associative array.
   *
   * @var bool
   */
  protected $__isAssociative;

  /**
   * Flag indicating whether the object is an indexed array.
   *
   * @var bool
   */
  protected $__isIndexed;

  /**
   * Flag indicating whether the object is a sequentially indexed array.
   *
   * @var bool
   */
  protected $__isSequential;

  /**
   * The iterator class.
   *
   * @var string
   */
  protected $__iteratorClass;

  /**
   * These protected properties.
   *
   * @var array
   */
  protected $__protectedProperties;

  /**
   * The internal storage of the object.
   *
   * @var array|mixed
   */
  protected $__storage;

  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  public function __construct(&$values = [], $flags = self::STD_PROP_LIST | self::ARRAY_AS_PROPS, $iterator_class = '\ArrayIterator') {
    $this->setFlags($flags);
    $this->setIteratorClass($iterator_class);
    $this->__protectedProperties = array_keys(get_object_vars($this));

    // Convert anything other than a simple array.
    $values =& $this->convertArgument($values);

    $merge = [];
    if (is_array($values) || $values instanceof \Traversable) {
      // In order to properly set objects, they must be passed through the
      // ::merge method will will ultimately invoke ::convertValue which may
      // be subclassed. Unfortunately, to do this and not break any references,
      // that requires each of values to be temporarily moved to a different
      // array (by reference) and then merged back in after the original array
      // has be set.
      foreach ($values as $key => &$value) {
        $merge[$key] =& $value;
        unset($values[$key]);
      }

      // In cases where this may be an indexed array, the actual index is not
      // reset after each unset() call above. Some solutions point to using
      // array_values() to reset this index, but that would also destroy any
      // passed reference. Instead, use array_splice() which keeps any reference
      // and resets the index. This is safe to use here because all the values
      // were just moved to the temporary $merge array.
      array_splice($values, 0, 1);
    }

    // It is now safe to reference the original values array.
    $this->__storage =& $values;

    // Merge in any original values.
    if ($merge) {
      $this->merge($merge);
    }

    // Reset changed status.
    $this->changed(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(...$arguments) {
    // Only clone the first object.
    if (isset($arguments[0])) {
      $values = $arguments[0];
      if (is_object($values)) {
        $values = clone $values;
      }
      $arguments[0] = $values;
    }
    return static::reference(...$arguments);
  }

  /**
   * {@inheritdoc}
   */
  public static function reference(&...$arguments) {
    // Convert any StaticArray instances into a normal array (by reference) so
    // a new object instance can be created. This is needed to allow any
    // additional parameters (if subclassed) to be sent to the constructor,
    // which gives the illusion of "updating" references as needed.
    if (isset($arguments[0])) {
      if ($arguments[0] instanceof static) {
        $arguments[0] =& $arguments[0]->value();
      }
    }
    return new static(...$arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function &__get($key) {
    $ret = NULL;
    if ($this->__flags == self::ARRAY_AS_PROPS) {
      $ret =& $this->offsetGet($key);

      return $ret;
    }
    if (in_array($key, $this->__protectedProperties)) {
      throw new \InvalidArgumentException('$key is a protected property, use a different key');
    }

    return $this->$key;
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($key) {
    if ($this->__flags == self::ARRAY_AS_PROPS) {
      return $this->offsetExists($key);
    }
    if (in_array($key, $this->__protectedProperties)) {
      throw new \InvalidArgumentException('$key is a protected property, use a different key');
    }

    return isset($this->$key);
  }

  /**
   * {@inheritdoc}
   */
  public function __set($key, $value) {
    $value = $this->convertValue($key, $value);

    if ($this->__flags == self::ARRAY_AS_PROPS) {
      $this->offsetSet($key, $value);
    }
    if (in_array($key, $this->__protectedProperties)) {
      throw new \InvalidArgumentException('$key is a protected property, use a different key');
    }
    $this->$key = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($key) {
    if ($this->__flags == self::ARRAY_AS_PROPS) {
      $this->offsetUnset($key);
    }
    if (in_array($key, $this->__protectedProperties)) {
      throw new \InvalidArgumentException('$key is a protected property, use a different key');
    }
    unset($this->$key);
  }

  /**
   * {@inheritdoc}
   */
  public function append($value, $key = NULL) {
    $original = $this->__storage;
    $value = $this->convertValue($key, $value);

    $this->__storage += isset($key) ? [$key => $value] : [$value];

    if (!$this->equals($original)) {
      $this->changed();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function asort() {
    if (is_array($this->__storage)) {
      asort($this->__storage);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function count($mode = COUNT_NORMAL) {
    if (is_array($this->__storage) || is_object($this->__storage)) {
      return count($this->__storage, $mode);
    }
    elseif (is_string($this->__storage)) {
      return strlen($this->__storage);
    }
    return (int) !!$this->__storage;
  }

  /**
   * Base flatten.
   *
   * @param array $array
   *   The array to iterate over.
   * @param bool $shallow
   *   Flag indicating whether the array will only flatten a single level.
   * @param bool $strict
   *   Flag indicating whether to only flatten arrays.
   *
   * @return array
   *   The flattened array.
   *
   * @see https://github.com/tajawal/lodash-php/blob/master/src/arrays/flatten.php
   */
  protected function baseFlatten(array $array, $shallow = FALSE, $strict = TRUE) {
    $output = [];
    $idx = 0;
    foreach ($array as $index => $value) {
      if (is_array($value)) {
        if (!$shallow) {
          $value = $this->baseFlatten($value, $shallow, $strict);
        }
        $j = 0;
        $len = count($value);
        while ($j < $len) {
          $output[$idx++] = $value[$j++];
        }
      }
      else {
        if (!$strict) {
          $output[$idx++] = $value;
        }
      }
    }
    return $output;
  }

  /**
   * Indicates when the array has changed.
   *
   * @param bool $changed
   *   Flag indicating whether value has changed.
   *
   * @return static
   */
  protected function changed($changed = TRUE) {
    $this->__changed = $changed;

    // Reset any cached values on the type of array.
    unset($this->__isAssociative, $this->__isSequential);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function copy() {
    return static::create($this->__storage);
  }

  /**
   * Converts an argument into a simple array.
   *
   * @param array|object|StaticArray $argument
   *   The argument to convert.
   *
   * @return array
   *   The converted argument.
   *
   * @throws \InvalidArgumentException
   *   When argument cannot be converted.
   */
  protected function &convertArgument(&$argument) {
    // Immediately return if argument is empty.
    if (empty($argument)) {
      return $argument;
    }

    // Convert objects into an StaticArray.
    if (is_object($argument) && !($argument instanceof \ArrayObject)) {
      $argument = (new \ArrayObject($argument))->getArrayCopy();
    }

    // Convert StaticArray to array.
    while ($argument instanceof \ArrayObject) {
      $argument = $argument->getArrayCopy();
    }

    return $argument;
  }

  /**
   * Converts multiple arguments into an array of simple arrays.
   *
   * @param array $arguments
   *   An array of arguments.
   *
   * @return array
   *   The converted array.
   */
  protected function &convertArguments(array &$arguments = []) {
    // Iterate over the arguments and add it to the converted return array.
    foreach ($arguments as &$argument) {
      $array[] = &$this->convertArgument($argument);
    }
    return $arguments;
  }

  /**
   * Converts a value before storing it.
   *
   * @param string $key
   *   The name of the value being stored.
   * @param mixed $value
   *   The value to convert, passed by reference.
   *
   * @return mixed
   *   The converted value.
   */
  protected function convertValue(/* @noinspection PhpUnusedParameterInspection */ $key, $value = NULL) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function equals($value) {
    return $this->__storage === static::create($value)->value();
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Use ::replace() instead.
   */
  public function exchangeArray($input) {
    $this->replace($input);
  }

  /**
   * {@inheritdoc}
   */
  public function exists($key, $check_key = TRUE) {
    if ($this->isSequential()) {
      $key = array_search($key, $this->__storage);
      return $key !== FALSE;
    }
    return $check_key ? array_key_exists($key, $this->__storage) : $this->offsetExists($key);
  }

  /**
   * {@inheritdoc}
   */
  public function find($key, $default = NULL) {
    if (@preg_match('/^\/[\s\S]+\/$/', $key)) {
      return $this->findFirst($key, $default);
    }
    // Last ditch effort.
    return $this->get($key, $default, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function &findAll($pattern) {
    $found = [];
    foreach ($this->__storage as $key => $value) {
      $item = $this->isSequential() ? $value : $key;
      if (is_string($item) && @preg_match($pattern, $item)) {
        $found[$key] = $this->get($item);
      }
    }
    return $found;
  }

  /**
   * {@inheritdoc}
   */
  public function findFirst($pattern, $default = NULL) {
    $results = $this->findAll($pattern);
    return $results ? reset($results) : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function findLast($pattern, $default = NULL) {
    $results = $this->findAll($pattern);
    return $results ? end($results) : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function filter($callback = NULL, $flag = 0) {
    $original = $this->value();

    // Immediately return if value isn't an array.
    if (!is_array($original)) {
      return $this;
    }

    if (!isset($callback)) {
      $callback = function ($val) {
        return (bool) $val;
      };
    }

    $this->__storage = array_filter($original, $callback, $flag);

    if (!$this->equals($original)) {
      $this->changed();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function flatten($shallow = FALSE) {
    $original = $this->value();

    // Immediately return if value isn't an array.
    if (!is_array($original)) {
      return $this;
    }

    $this->__storage = $this->baseFlatten($original, $shallow, FALSE);

    if (!$this->equals($original)) {
      $this->changed();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &get($key, $default = NULL, $set_if_no_existence = TRUE) {
    if (!$this->exists($key)) {
      if (!$set_if_no_existence) {
        return $default;
      }
      $this->set($key, $default);
    }
    $ret = &$this->offsetGet($key);
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function getArrayCopy() {
    $storage = $this->__storage;
    if (is_array($storage)) {
      return $storage;
    }
    elseif (is_object($storage)) {
      return (array) $storage;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFlags() {
    return $this->__flags;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $class = $this->getIteratorClass();
    return new $class($this->__storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getIteratorClass() {
    return $this->__iteratorClass;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanged() {
    return $this->__changed;
  }

  /**
   * {@inheritdoc}
   */
  public function isAssociative() {
    if (!isset($this->__isAssociative)) {
      // Explicitly set to FALSE if not an array or an empty array.
      if (!is_array($this->__storage) || $this->__storage === []) {
        $this->__isAssociative = FALSE;
      }
      // Otherwise, determine by checking if the keys are strings.
      else {
        $this->__isAssociative = count(array_filter(array_keys($this->__storage), 'is_string'));
      }
    }
    return $this->__isAssociative;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->__storage);
  }

  /**
   * {@inheritdoc}
   */
  public function isIndexed() {
    if (!isset($this->__isIndexed)) {
      // Explicitly set to FALSE if not an array.
      if (!is_array($this->__storage)) {
        $this->__isIndexed = FALSE;
      }
      // Explicitly set to TRUE if the array is empty.
      elseif ($this->__storage === []) {
        $this->__isIndexed = TRUE;
      }
      // Otherwise, determine by checking if the keys are numeric.
      else {
        $this->__isIndexed = count(array_filter(array_keys($this->__storage), 'is_numeric'));
      }
    }
    return $this->__isIndexed;
  }

  /**
   * {@inheritdoc}
   */
  public function isSequential() {
    if (!isset($this->__isSequential)) {
      // Explicitly set to FALSE if not an array.
      if (!is_array($this->__storage)) {
        $this->__isSequential = FALSE;
      }
      // Explicitly set to TRUE if the array is empty.
      elseif ($this->__storage === []) {
        $this->__isSequential = TRUE;
      }
      // Otherwise, determine by checking if the keys match a range.
      else {
        $this->__isSequential = array_keys($this->__storage) === range(0, count($this->__storage) - 1);
      }
    }
    return $this->__isSequential;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    return $this->getArrayCopy();
  }

  /**
   * {@inheritdoc}
   */
  public function ksort() {
    if (is_array($this->__storage)) {
      ksort($this->__storage);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function keys() {
    return array_keys($this->__storage);
  }

  /**
   * {@inheritdoc}
   */
  public function map(callable $callback) {
    $value = &$this->value();
    $original = $value;

    // Immediately return if value isn't an array.
    if (!is_array($value)) {
      return $this;
    }

    foreach ($value as $k => &$v) {
      $value[$k] = call_user_func_array($callback, [&$v, $k, $value]);
    }

    $this->__storage = &$value;

    if (!$this->equals($original)) {
      $this->changed();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function merge(&...$arguments) {
    if (is_array($this->__storage)) {
      $original = $this->__storage;
      $this->mergeByReference($this->__storage, $this->convertArguments($arguments));
      if (!$this->equals($original)) {
        $this->changed();
      }
    }
    return $this;
  }

  /**
   * Merges arguments onto an array passed by reference.
   *
   * @param array $array
   *   The array to merge arguments onto, passed by reference.
   * @param array $arguments
   *   The arguments to merge onto $array, passed by reference.
   * @param bool $recurse
   *   (optional) Flag indicating whether to recursively merge. Defaults to
   *   FALSE.
   * @param bool $preserve_integer_keys
   *   (optional) Flag indicating whether integer keys will be preserved and
   *   merged instead of appended. Defaults to FALSE.
   */
  protected function mergeByReference(array &$array = [], array &$arguments = [], $recurse = FALSE, $preserve_integer_keys = FALSE) {
    foreach ($arguments as &$argument) {
      // Skip any non-traversable objects.
      if (!is_array($argument) && !($argument instanceof \Traversable)) {
        continue;
      }
      foreach ($argument as $key => &$value) {
        // Renumber integer keys as array_merge_recursive() does unless
        // $preserve_integer_keys is set to TRUE. Note that PHP automatically
        // converts keys that are integer strings (e.g. '1') to integers.
        if (is_int($key) && !$preserve_integer_keys) {
          $array[] = $this->convertValue($key, $value);
        }
        // Recurse when both values are arrays.
        elseif ($recurse && isset($array[$key]) && is_array($array[$key]) && is_array($value)) {
          $array[$key] = $this->mergeByReference($array[$key], $value, $recurse);
        }
        // Otherwise, use the latter value, overriding any previous value.
        else {
          $array[$key] = $this->convertValue($key, $value);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mergeDeep(&...$arguments) {
    if (is_array($this->__storage)) {
      $original = $this->__storage;
      $this->mergeByReference($this->__storage, $this->convertArguments($arguments), TRUE);
      if (!$this->equals($original)) {
        $this->changed();
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function natcasesort() {
    if (is_array($this->__storage)) {
      natcasesort($this->__storage);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function natsort() {
    if (is_array($this->__storage)) {
      natsort($this->__storage);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($key) {
    return isset($this->__storage[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function &offsetGet($key) {
    $ret = NULL;
    if (!$this->offsetExists($key)) {
      return $ret;
    }
    $ret =& $this->__storage[$key];

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($key, $value) {
    $original = $this->__storage;
    $value = $this->convertValue($key, $value);
    if (isset($key)) {
      $this->__storage[$key] = $value;
    }
    else {
      $this->__storage[] = $value;
    }
    if (!$this->equals($original)) {
      $this->changed();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($key) {
    $original = $this->__storage;
    // Look for an associative key to remove.
    if ($this->isAssociative()) {
      if ($this->offsetExists($key)) {
        unset($this->__storage[$key]);
      }
    }
    // Otherwise, it's a value that should be removed.
    elseif ($this->isSequential()) {
      $key = array_search($key, $this->__storage, TRUE);
      if ($key !== FALSE) {
        array_splice($this->__storage, $key, 1);
      }
    }
    if (!$this->equals($original)) {
      $this->changed();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function prepend(&$value, $key = NULL) {
    $original = $this->__storage;
    $value = $this->convertValue($key, $value);

    if (isset($key)) {
      $this->__storage = [$key => &$value] + $this->__storage;
    }
    else {
      $this->__storage = [&$value] + $this->__storage;
    }

    if (!$this->equals($original)) {
      $this->changed();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function remove(...$keys) {
    $original = $this->__storage;

    $keys = StaticArray::create($keys)->flatten()->value();
    foreach ($keys as $key) {
      $this->offsetUnset($key);
    }

    if (!$this->equals($original)) {
      $this->changed();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function replace(array &$value = [], array &$previous = []) {
    $previous = $this->__storage;
    $this->__storage = $this->convertArgument($value);

    if (!$this->equals($previous)) {
      $this->changed(FALSE);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    $data = get_object_vars($this);

    // Remove unnecessary properties.
    unset($data['__changed'], $data['__isAssociative'], $data['__isIndexed'], $data['__isSequential'], $data['__protectedProperties']);

    return serialize($data);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value = NULL) {
    $this->offsetSet($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFlags($flags) {
    $this->__flags = $flags;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setIteratorClass($class) {
    if (is_object($class)) {
      $class = get_class($class);
    }

    if (is_string($class) && strpos($class, '\\') !== 0) {
      $class = '\\' . $class;
    }

    if (!class_exists($class)) {
      throw new \InvalidArgumentException('The iterator class does not exist');
    }

    $this->__iteratorClass = $class;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function similar($value) {
    // This is intentionally using == as the check is not strict on purpose.
    // @codingStandardsIgnoreStart
    return $this->__storage == static::create($value)->value();
    // @codingStandardsIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function uasort($callback) {
    if (is_array($this->__storage) && is_callable($callback)) {
      uasort($this->__storage, $callback);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function uksort($callback) {
    if (is_array($this->__storage) && is_callable($callback)) {
      uksort($this->__storage, $callback);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unique($sort_flags = SORT_STRING) {
    $original = $this->value();

    // Immediately return if value isn't an array.
    if (!is_array($original)) {
      return $this;
    }

    $this->__storage = array_unique($original, $sort_flags);

    if (!$this->equals($original)) {
      $this->changed();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $data = unserialize($serialized);
    $this->__protectedProperties = array_keys(get_object_vars($this));

    $this
      ->setIteratorClass($data['__iteratorClass'])
      ->setFlags($data['__flags'])
      ->replace($data['__storage']);

    unset($data['__iteratorClass'], $data['__flags'], $data['__storage']);

    // Set any other properties (due to flag).
    foreach ($data as $key => $value) {
      $this->__set($key, $value);
    }

    // Reset changed status.
    $this->changed(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function &value() {
    return $this->__storage;
  }

  /**
   * {@inheritdoc}
   */
  public function walk(callable $callback, $recursive = FALSE, $user_data = NULL) {
    $value = &$this->value();
    $original = $value;

    // Immediately return if value isn't an array.
    if (!is_array($value)) {
      return $this;
    }

    if ($recursive) {
      array_walk_recursive($value, $callback, $user_data);
    }
    else {
      array_walk($value, $callback, $user_data);
    }

    $this->__storage =& $value;

    if (!$this->equals($original)) {
      $this->changed();
    }

    return $this;
  }

}
