<?php

/**
 * AcsfConfigUnitTest.
 */
class AcsfConfigUnitTest extends \Drupal\acsf\AcsfConfig {

  /**
   * Implements AcsfConfig::loadConfig().
   */
  protected function loadConfig() {
    $this->url = 'http://gardener.unit.test';
    $this->username = 'gardener_unit_test';
    $this->password = 'Un1tT35t';
  }

}
