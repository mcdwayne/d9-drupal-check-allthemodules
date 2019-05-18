<?php

namespace Drupal\developer_suite\Validator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\developer_suite\Collection\ViolationCollectionInterface;

/**
 * Class FormValidator.
 *
 * @package Drupal\developer_suite\Validator
 */
abstract class FormValidator extends BaseValidator implements FormValidatorInterface {

  /**
   * The form element name.
   *
   * @var string
   */
  private $element;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  private $formState;

  /**
   * FormValidator constructor.
   *
   * @param string $message
   *   The violation message.
   * @param string $element
   *   The form element name to be validated.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   */
  public function __construct($message, $element, FormStateInterface $formState) {
    parent::__construct($message);

    $this->element = $element;
    $this->formState = $formState;
  }

  /**
   * Validates a form value.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\developer_suite\Collection\ViolationCollectionInterface $violationCollection
   *   The violations.
   */
  abstract public function validate($value, ViolationCollectionInterface $violationCollection);

  /**
   * Returns the validated form element name.
   *
   * @return string
   *   The validated form element name.
   */
  public function getElement() {
    return $this->element;
  }

  /**
   * Returns the form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state.
   */
  public function getFormState() {
    return $this->formState;
  }

}
