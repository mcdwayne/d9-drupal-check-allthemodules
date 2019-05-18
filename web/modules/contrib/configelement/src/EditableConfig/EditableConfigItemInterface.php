<?php

namespace Drupal\configelement\EditableConfig;

/**
 * Class EditableConfigItem
 *
 * Wraps an editable config item, which is a sub-array of a config object's
 * data array. It can
 * - care that the concerned config object are autosaved (if changed) only once
 *   (even if different config items share the same config object).
 * - validate the concerned config objects via typed data validations.
 *
 * @package Drupal\configelement\EditableConfig
 */
interface EditableConfigItemInterface {

    /**
   * Get name.
   *
   * @return string
   */
  public function getName();

  /**
   * Get label.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Get schema class.
   *
   * @return string
   */
  public function getSchemaClass();

  /**
   * Get form element.
   *
   * @return string
   */
  public function getFormElementType();

  /**
   * Set a value.
   *
   * @param mixed $value
   */
  public function setValue($value);

  /**
   * Get value.
   *
   * @return mixed
   */
  public function getValue();

  /**
   * Add this as a cacheable dependency.
   *
   * @param array $element
   *   The render element.
   *
   * @return
   */
  public function addCachableDependencyTo(array &$element);

  /**
   * @param $name
   *
   * @return EditableConfigItemInterface
   */
  public function get($name);

  /**
   * Get children.
   *
   * @return EditableConfigItemInterface[]
   */
  public function getElements();

  /**
   * @return bool
   */
  public function isList();

  /**
   * Validate values.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   */
  public function validate();

}
