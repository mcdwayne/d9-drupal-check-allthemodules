<?php

namespace Drupal\plus\Utility;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\ToStringTrait;

/**
 * Replace core's horribly incomplete "Attribute" implementation.
 *
 * Note: this class has a JavaScript counterpart that is nearly identical
 * located at ./js/attributes.js.
 *
 * @ingroup utility
 *
 * @see \Drupal\Core\Template\Attribute
 *
 * @todo Replace all "Attribute" instances before the preprocess phase.
 * @todo Replace all "Attribute" instances during Twig rendering as a fallback.
 *
 * @method \Drupal\plus\Utility\AttributeBase __get($key)
 * @method \Drupal\plus\Utility\AttributeBase get($key, $default = NULL, $setIfNotExists = TRUE)
 * @method \Drupal\plus\Utility\AttributeBase offsetGet($key)
 */
class Attributes extends ArrayObject {

  use ToStringTrait;

  /**
   * Add class(es) to Attributes.
   *
   * @param string|string[] ...
   *   One or more classes to add.
   *
   * @return static
   *
   * @see \Drupal\plus\Utility\AttributeClasses
   * @see \Drupal\plus\Utility\Attributes::getClasses()
   */
  public function addClass(...$classes) {
    $this->getClasses()->merge(...$classes);
    return $this;
  }

  /**
   * Add class(es) to Attributes.
   *
   * @param string|string[] ...
   *   One or more data attributes to add.
   *
   * @return static
   *
   * @see \Drupal\plus\Utility\AttributeData
   * @see \Drupal\plus\Utility\Attributes::getData()
   */
  public function addData(...$data) {
    $this->getData()->merge(...$data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function convertValue($key, $value = NULL) {
    // If value is an AttributeValueBase object, clone it and set the new name.
    if ($value instanceof AttributeBase) {
      return $value->copy()->setName($key);
    }

    // Handle classes.
    if ($key === 'class') {
      // An array value or 'class' attribute name are forced to always be an
      // AttributeArray value for consistency.
      if (!is_array($value)) {
        // Cast the value to string in case it implements MarkupInterface.
        $value = [(string) $value];
      }
      return new AttributeClasses($value);
    }

    // Handle data attributes.
    if ($key === 'data') {
      return new AttributeData($value);
    }
    if (preg_match('/^data-/', $key)) {
      return new AttributeDataValue($key, $value);
    }

    // Handle arrays.
    if (is_array($value)) {
      return new AttributeArray($key, $value);
    }

    // Handle booleans.
    if (is_bool($value)) {
      return new AttributeBoolean($key, $value);
    }

    // As a development aid, we allow the value to be a safe string object.
    if ($value instanceof MarkupInterface) {
      // Attributes are not supposed to display HTML markup, so we just convert
      // the value to plain text.
      $value = PlainTextOutput::renderFromHtml($value);
      return new AttributeString($key, $value);
    }

    if (!is_object($value)) {
      return new AttributeString($key, $value);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getArrayCopy() {
    $array = [];
    /* @var \Drupal\Core\Template\AttributeValueBase $value */
    foreach (parent::getArrayCopy() as $name => $value) {
      $array[$name] = $value->value();
    }
    return $array;
  }

  /**
   * Retrieves classes.
   *
   * @return \Drupal\plus\Utility\AttributeClasses
   *   An AttributeClasses object.
   *
   * @see \Drupal\plus\Utility\ArrayObject::offsetGet()
   */
  public function getClasses() {
    if (!isset($this['class'])) {
      $this['class'] = new AttributeClasses();
    }
    return $this['class'];
  }

  /**
   * Retrieves data attributes.
   *
   * @return \Drupal\plus\Utility\AttributeData
   *   An AttributeData object.
   *
   * @see \Drupal\plus\Utility\ArrayObject::offsetGet()
   */
  public function getData() {
    if (!isset($this['data'])) {
      $this['data'] = new AttributeData();
    }
    return $this['data'];
  }

  /**
   * Indicates whether a class is present in the array.
   *
   * @param string|array $class
   *   The class or array of classes to search for.
   * @param bool $all
   *   Flag determining to check if all classes are present.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\plus\Utility\Attributes::getClasses()
   */
  public function hasClass($class, $all = FALSE) {
    $classes = (array) $class;
    $result = array_intersect($classes, $this->getClasses()->getArrayCopy());
    return $all ? $result && count($classes) === count($result) : !!$result;
  }

  /**
   * {@inheritdoc}
   */
  public function merge(&...$arguments) {
    $this->convertArguments($arguments);

    // Handle classes and data attributes uniquely.
    $classes = [];
    $data = [];
    foreach ($arguments as &$argument) {
      if (isset($argument['class'])) {
        $classes[] = &$argument['class'];
        unset($argument['class']);
      }
      if (isset($argument['data'])) {
        $data[] = &$argument['data'];
        unset($argument['data']);
      }
    }

    // Merge in any classes.
    if ($classes) {
      $this->addClass($classes);
    }

    // Merge in any data.
    if ($data) {
      $this->addData($data);
    }

    // Merge in the rest of the attributes.
    $this->mergeByReference($this->__storage, $arguments);

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated There are no "deep" multi-dimensional arrays in attributes.
   *   This just proxies to the ::merge method.
   */
  public function mergeDeep(&...$arguments) {
    return $this->merge(...$arguments);
  }

  /**
   * Removes an attribute from an Attribute object.
   *
   * @param string|string[] ...
   *   Attributes to remove from the attribute array.
   *
   * @return $this
   *
   * @deprecated Use ::remove() instead.
   */
  public function removeAttribute(...$arguments) {
    return $this->remove(...$arguments);
  }

  /**
   * Removes a class from the attributes array.
   *
   * @param string|string[] ...
   *   Class(es) to remove from the attribute array.
   *
   * @return static
   *
   * @see \Drupal\plus\Utility\Attributes::getClasses()
   */
  public function removeClass(...$classes) {
    $this->getClasses()->remove(...$classes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $output = '';
    /** @var \Drupal\plus\Utility\AttributeBase $value */
    foreach ($this->value() as $name => $value) {
      $rendered = $value->render();
      if ($rendered) {
        $output .= ' ' . $rendered;
      }
    }
    return $output;
  }

  /**
   * Replaces a class in the attributes array.
   *
   * @param string $oldClassName
   *   The old class to remove.
   * @param string $newClassName
   *   The new class. It will not be added if the $old class does not exist.
   * @param bool $onlyIfExists
   *   (optional) Flag indicating whether to add $newClassName only if
   *   $oldClassName exists, defaults to TRUE.
   *
   * @return static
   *
   * @see \Drupal\plus\Utility\Attributes::getClasses()
   */
  public function replaceClass($oldClassName, $newClassName, $onlyIfExists = TRUE) {
    $this->getClasses()->replaceClass($oldClassName, $newClassName, $onlyIfExists);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value = NULL) {
    // Handle class attribute differently.
    if ($key === 'class') {
      $this->getClasses()->replace();
      return $this->addClass($value);
    }
    if ($key === 'data') {
      $this->getData()->replace();
      return $this->addData($value);
    }
    return parent::set($key, $value);
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Use ::set() instead.
   */
  public function setAttribute($key, $value) {
    return $this->set($key, $value);
  }

  /**
   * Sets multiple attributes on the array.
   *
   * @param array $values
   *   An associative key/value array of attributes to set.
   *
   * @see \Drupal\plus\Utility\ArrayObject::merge()
   */
  public function setAttributes(array $values) {
    // Handle class attribute differently.
    $classes = isset($values['class']) ? $values['class'] : [];
    unset($values['class']);
    if ($classes) {
      $this->addClass($classes);
    }

    $data = isset($values['data']) ? $values['data'] : [];
    unset($values['data']);
    if ($data) {
      $this->addData($data);
    }

    // Merge the reset of the attributes.
    $this->merge($values);
  }

  /**
   * Returns the whole array.
   *
   * @deprecated Use ::value() instead.
   */
  public function storage() {
    return $this->value();
  }

}
