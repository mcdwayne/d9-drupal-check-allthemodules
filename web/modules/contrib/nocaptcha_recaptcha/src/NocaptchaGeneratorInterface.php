<?php

namespace Drupal\nocaptcha_recaptcha;

/**
 * Interface NocaptchaGeneratorInterface.
 *
 * @package Drupal\nocaptcha_recaptcha
 */
interface NocaptchaGeneratorInterface {

  /**
   * @return mixed
   */
  public function generate();
}
