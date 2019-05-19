<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

class ConfigFactoryDummy implements ConfigFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function get($name) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getEditable($name) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $names) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function reset($name = NULL) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function rename($old_name, $new_name) {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheKeys() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCache() {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    // Intentionally left empty. Dummies don't do anything.
  }

  /**
   * {@inheritdoc}
   */
  public function addOverride(ConfigFactoryOverrideInterface $config_factory_override) {
    // Intentionally left empty. Dummies don't do anything.
  }
}
