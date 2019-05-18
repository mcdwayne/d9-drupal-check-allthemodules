<?php

namespace Drupal\developer_suite\Validator;

/**
 * Interface ValidatorInterface.
 *
 * @package Drupal\developer_suite\Validator
 */
interface FormValidatorInterface extends BaseValidatorInterface {

  /**
   * Returns the validated form element name.
   *
   * @return string
   *   The validated form element name.
   */
  public function getElement();

  /**
   * Returns the form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state.
   */
  public function getFormState();

}
