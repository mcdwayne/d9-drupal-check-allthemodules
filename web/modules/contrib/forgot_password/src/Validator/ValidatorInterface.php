<?php
namespace Drupal\forgot_password\Validator;

/**
 * Interface ValidatorInterface.
 *
 * @package Drupal\forgot_password\Validator
 */
interface ValidatorInterface {

  /**
   * Returns bool indicating if validation is ok.
   */
  public function validates($field, $value);

  /**
   * Returns error message.
   */
  public function getErrorMessage();

}
