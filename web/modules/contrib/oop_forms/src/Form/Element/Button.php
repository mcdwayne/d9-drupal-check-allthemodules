<?php

namespace Drupal\oop_forms\Form\Element;

class Button extends Element {

  /**
   * The text to be shown on the button.
   *
   * @var string
   */
  protected $value;

  /**
   * An array of form element keys that will block form submission when
   * validation for these elements or any child elements fails.
   * Specify an empty array to suppress all form validation errors.
   *
   * @var false|string[]
   */
  protected $limitValidationErrors = false;

  /**
   * Button constructor.
   */
  public function __construct() {
    return parent::__construct('button');
  }

  /**
   * Gets the text to be shown on the button.
   *
   * @return string
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Sets the text to be shown on the button.
   *
   * @param string $value
   *
   * @return Button
   */
  public function setValue($value) {
    $this->value = $value;

    return $this;
  }

  /**
   * @return false|\string[]
   */
  public function getLimitValidationErrors() {
    return $this->limitValidationErrors;
  }

  /**
   * @param false|\string[] $limitValidationErrors
   *
   * @return Button
   */
  public function setLimitValidationErrors($limitValidationErrors) {
    $this->limitValidationErrors = $limitValidationErrors;

    return $this;
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $form = parent::build();

    Element::addParameter($form, 'value', $this->value);
    Element::addParameter($form, 'limit_validation_errors', $this->limitValidationErrors);

    return $form;
  }


}
