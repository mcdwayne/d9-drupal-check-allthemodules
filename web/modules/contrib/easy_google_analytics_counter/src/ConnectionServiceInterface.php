<?php

namespace Drupal\easy_google_analytics_counter;

/**
 * Interface ConnectionServiceInterface.
 */
interface ConnectionServiceInterface {

  /**
   * Prepare the google analytics request.
   */
  public function request();

}
