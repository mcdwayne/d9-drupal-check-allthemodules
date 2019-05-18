<?php

namespace Drupal\TestSite;

/**
 * Setup file for Autodrop Nightwatch tests.
 */
class NightwatchSetup implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install([
      'autodrop',
      'autodrop_test_page',
    ]);
  }
}
