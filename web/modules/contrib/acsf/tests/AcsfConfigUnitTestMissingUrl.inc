<?php

/**
 * AcsfConfigUnitTestMissingUrl.
 */
class AcsfConfigUnitTestMissingUrl extends \Drupal\Acsf\AcsfConfig {

  /**
   * Implements AcsfConfig::loadConfig().
   */
  protected function loadConfig() {
    $this->username = 'gardener_unit_test';
    $this->password = 'Un1tT35t';
  }

}
