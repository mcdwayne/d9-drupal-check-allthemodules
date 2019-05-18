<?php

namespace Drupal\amp_validator;

/**
 * Provides an interface defining an AMP validator.
 */
interface AmpValidatorInterface {

  /**
   * Get errors after running AMP validation.
   */
  public function getErrors();

  /**
   * Returns AMP valid status.
   */
  public function isValid();

  /**
   * Run AMP validation.
   */
  public function validate();

}
