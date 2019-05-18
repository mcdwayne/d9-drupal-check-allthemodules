<?php

namespace Drupal\plus\Utility;

use Drupal\Component\Utility\Html;

/**
 * A class that defines a type of Attribute that can be added to as an array.
 *
 * To use with Attribute, the array must be specified.
 * Correct:
 * @code
 *  $attributes = new Attribute();
 *  $attributes['class'] = array();
 *  $attributes['class'][] = 'cat';
 * @endcode
 * Incorrect:
 * @code
 *  $attributes = new Attribute();
 *  $attributes['class'][] = 'cat';
 * @endcode
 *
 * @see \Drupal\Core\Template\Attribute
 */
class AttributeArray extends AttributeBase {

  /**
   * Ensures empty array as a result of array_filter will not print '$name=""'.
   *
   * @see \Drupal\Core\Template\AttributeArray::renderValue()
   * @see \Drupal\Core\Template\AttributeValueBase::render()
   */
  const RENDER_EMPTY_ATTRIBUTE = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct($name, &$value = NULL) {
    if (!isset($value)) {
      $value = [];
    }
    parent::__construct($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __set($key, $value) {
    parent::__set($key, $value);

    // After the value was added using object notation, e.g. $attr->0, ensure
    // unique and non-empty values. Note: this is highly unlikely as it's
    // generally considered poor DX. However, do it just in case.
    $this->filter()->unique();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return Html::escape(implode(' ', $this->value()));
  }

  /**
   * {@inheritdoc}
   */
  protected function &convertArguments(array &$arguments = []) {
    $arguments = [$this->sanitize(...$arguments)->value()];
    return parent::convertArguments($arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function merge(&...$values) {
    parent::merge(...$values);

    // After values were merged in, ensure unique and non-empty values.
    return $this->filter()->unique();
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($key, $value) {
    parent::offsetSet($key, $value);

    // After the value was added using array notation, e.g. $attr[], ensure
    // unique and non-empty values.
    $this->filter()->unique();
  }

  /**
   * {@inheritdoc}
   */
  public function replace(array &$value = [], array &$previous = []) {
    parent::replace($value, $previous);;

    // After entire values are replaced, ensure unique and non-empty values.
    return $this->filter()->unique();
  }

  /**
   * Sanitizes values that are about to be merged in.
   *
   * @param mixed ...
   *   The values to sanitize.
   *
   * @return \Drupal\plus\Utility\ArrayObject
   *   A new ArrayObject instance with the values to merge.
   */
  protected function sanitize(...$values) {
    // Since attributes do not have the concept of multi-dimensional arrays,
    // flatten it into a single array of values.
    return ArrayObject::create()->merge(...$values)->flatten();
  }

  /**
   * {@inheritdoc}
   */
  public function remove(...$keys) {
    $this->convertArguments($keys);
    return parent::remove(...$keys);
  }

}
