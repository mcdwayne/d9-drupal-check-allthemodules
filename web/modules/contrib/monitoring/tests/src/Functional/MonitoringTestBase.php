<?php

namespace Drupal\Tests\monitoring\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for all monitoring web tests.
 */
abstract class MonitoringTestBase extends BrowserTestBase {

  use MonitoringTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'monitoring', 'monitoring_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

}
