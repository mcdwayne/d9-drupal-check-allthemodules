<?php

/**
 * This simple interface defines the way that responses are constructed.
 */
class AcsfMessageResponseUnitTest extends \Drupal\acsf\AcsfMessageResponse {

  /**
   * Determines whether the test failed.
   *
   * @return bool
   *   Whether the test failed.
   */
  public function failed() {
    if ($this->code) {
      return TRUE;
    }
    return FALSE;
  }

}
