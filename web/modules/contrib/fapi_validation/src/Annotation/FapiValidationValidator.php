<?php

namespace Drupal\fapi_validation\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * A FAPI Validation Validator annotation.
 *
 * @Annotation
 */
class FapiValidationValidator extends Plugin {

  /**
   * The error message.
   *
   * @var string
   */
  protected $error_message;

  /**
   * The callback for error messages.
   *
   * @var string
   */
  protected $error_callback;

}
