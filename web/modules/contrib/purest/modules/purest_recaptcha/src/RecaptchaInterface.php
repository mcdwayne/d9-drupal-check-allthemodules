<?php

namespace Drupal\purest_recaptcha;

/**
 * Interface RecaptchaInterface.
 */
interface RecaptchaInterface {

  /**
   * Validates a reCAPTCHA response.
   *
   * @return bool
   *   Whether or not the response is valid.
   */
  public function validate(string $recaptcha_response);

}
