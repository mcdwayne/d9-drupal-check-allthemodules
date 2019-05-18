<?php

namespace Drupal\oop_forms\Form\Element;


class Number extends Element {

  /**
   * Minimum value.
   *
   * @var float
   */
  protected $min;

  /**
   * Maximum value.
   *
   * @var float
   */
  protected $max;

  /**
   * Ensures that the number is an even multiple of step,
   * offset by $min if specified. A $min of 1 and a $step of 2
   * would allow values of 1, 3, 5, etc.
   *
   * @var float
   */
  protected $step = 1;

  /**
   * Number constructor.
   */
  public function __construct() {
    return parent::__construct('number');
  }

  /**
   * @param float $defaultValue
   *
   * @return Element
   *
   * @throws \InvalidArgumentException when provided value is not float.
   */
  public function setDefaultValue($defaultValue) {
    if (!is_numeric($defaultValue)) {
      throw new \InvalidArgumentException(sprintf("Value '%s' is not a float value.", $defaultValue));
    }

    return parent::setDefaultValue($defaultValue);
  }

  /**
   * Gets minimum value.
   *
   * @return float
   */
  public function getMin() {
    return $this->min;
  }

  /**
   * Sets minimum value.
   *
   * @param float $min
   *
   * @return Number
   */
  public function setMin($min) {
    $this->min = $min;
    return $this;
  }

  /**
   * Gets maximum value.
   *
   * @return float
   */
  public function getMax() {
    return $this->max;
  }

  /**
   * Sets maximum value.
   *
   * @param float $max
   *
   * @return Number
   */
  public function setMax($max) {
    $this->max = $max;
    return $this;
  }

  /**
   * Gets step value.
   *
   * Ensures that the number is an even multiple of step,
   * offset by $min if specified. A $min of 1 and a $step of 2
   * would allow values of 1, 3, 5, etc.
   *
   * Default value is 1.
   *
   * @return float
   */
  public function getStep() {
    return $this->step;
  }

  /**
   * Sets step value.
   *
   * Ensures that the number is an even multiple of step,
   * offset by $min if specified. A $min of 1 and a $step of 2
   * would allow values of 1, 3, 5, etc.
   *
   * Default value is 1.
   *
   * @param float $step
   *
   * @return Number
   */
  public function setStep($step) {
    $this->step = $step;
    return $this;
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $form = parent::build();

    Element::addParameter($form, 'min', $this->min);
    Element::addParameter($form, 'max', $this->max);
    Element::addParameter($form, 'step', $this->step);

    return $form;
  }

}
