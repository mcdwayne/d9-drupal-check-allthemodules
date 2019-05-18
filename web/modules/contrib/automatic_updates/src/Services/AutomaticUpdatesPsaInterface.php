<?php

namespace Drupal\automatic_updates\Services;

/**
 * Interface AutomaticUpdatesPsaInterface.
 */
interface AutomaticUpdatesPsaInterface {

  /**
   * Get public service messages.
   *
   * @return array
   *   A return of translatable strings.
   */
  public function getPublicServiceMessages();

}
