<?php

/**
 * AcsfConfigUnitTestMissingUsername.
 */
class AcsfConfigUnitTestMissingUsername extends \Drupal\acsf\AcsfConfig {

  /**
   * Implements AcsfConfig::loadConfig().
   */
  protected function loadConfig() {
    $this->url = 'http://gardener.unit.test';
    $this->password = 'Un1tT35t';
  }

}
