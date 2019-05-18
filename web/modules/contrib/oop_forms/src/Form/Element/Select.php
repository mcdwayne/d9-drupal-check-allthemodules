<?php

namespace Drupal\oop_forms\Form\Element;

/**
 * Class Select
 * Provides a select form element.
 *
 * @TODO: Extract an ElementWithOptions class.
 *
 */
class Select extends Element {

  /**
   * An array of options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The label to show for the first default option.
   *
   * @var string
   */
  protected $emptyOption;

  /**
   * The value for the first default option
   *
   * @var string
   */
  protected $emptyValue;

  /**
   *  Indicates whether one or more options can be selected.
   *
   * @var bool
   */
  protected $multiple = FALSE;

  /**
   *  The size of the input element in characters.
   *
   * @var int
   */
  protected $size;

  /**
   * Item constructor.
   *
   */
  public function __construct() {
    return parent::__construct('select');
  }

  /**
   * Gets options array.
   *
   * @return array|string
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Sets options.
   *
   * @param array $options
   *
   * @return Select
   *
   * @throws \InvalidArgumentException when provided value is not an array.
   */
  public function setOptions($options) {
    if (!is_array($options)) {
      throw new \InvalidArgumentException(sprintf('Value %s is not an array.', $options));
    }
    $this->options = $options;

    return $this;
  }

  /**
   * Gets empty option property.
   *
   * @return string
   */
  public function getEmptyOption() {
    return $this->emptyOption;
  }

  /**
   * Sets empty option property
   *
   * The label to show for the first default option. By default, the label is
   * automatically set to "- Select -" for a required field and "- None -" for
   * an optional field.
   *
   * @param string $emptyOption
   *
   * @return Select
   */
  public function setEmptyOption($emptyOption) {
    $this->emptyOption = $emptyOption;

    return $this;
  }

  /**
   * Gets the empty value property.
   *
   * @return string
   */
  public function getEmptyValue() {
    return $this->emptyValue;
  }

  /**
   * Sets the empty value property.
   *
   * The value for the first default option, which is used to determine whether
   * the user submitted a value or not.
   *
   * @param $emptyValue string
   *
   * @return $this
   */
  public function setEmptyValue($emptyValue) {
    $this->emptyValue = $emptyValue;

    return $this;
  }

  /**
   * Gets multiple property value.
   *
   * @return bool
   */
  public function getMultiple() {
    return $this->multiple;
  }

  /**
   * Sets multiple property value.
   *
   * Indicates whether one or more options can be selected.
   *
   * @param bool $multiple
   *
   * @return Select
   */
  public function setMultiple($multiple = TRUE) {
    $this->multiple = $multiple;

    return $this;
  }

  /**
   * Gets size property.
   *
   * @return Select
   */
  public function getSize() {
    return $this->size;
  }

  /**
   * Sets size property.
   *
   * The size of the input element.
   *
   * @param int $size
   *
   * @return Select
   */
  public function setSize($size) {
    $this->size = $size;

    return $this;
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $element = parent::build();

    Element::addParameter($element, 'options', $this->options);
    Element::addParameter($element, 'multiple', $this->multiple);

    // Conditional properties.
    if ($this->emptyOption) {
      Element::addParameter($element, 'empty_option', $this->emptyOption);
    }
    if ($this->emptyValue) {
      Element::addParameter($element, 'empty_value', $this->emptyValue);
    }
    if ($this->size) {
      Element::addParameter($element, 'size', $this->size);
    }

    return $element;
  }


}
