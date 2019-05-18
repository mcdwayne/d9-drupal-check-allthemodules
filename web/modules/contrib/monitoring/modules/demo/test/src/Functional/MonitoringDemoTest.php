<?php
/**
 * @file
 * Contains \Drupal\monitoring_demo\Tests\MonitoringDemoTest.
 */

namespace Drupal\Tests\monitoring_demo\Functional;

use Drupal\Tests\monitoring\Functional\MonitoringTestBase;

/**
 * Tests the demo module for monitoring.
 *
 * @group monitoring
 */
class MonitoringDemoTest extends MonitoringTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = array('monitoring_demo');

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
  }

  /**
   * Asserts the demo instructions on the frontpage.
   */
  protected function testInstalled() {
    $this->drupalGet('');
    $this->assertText('Monitoring');
    $this->assertText(t('Welcome to the Monitoring demo installation.'));
    $this->assertLink(t('Monitoring sensors overview'));
    $this->assertLink(t('Monitoring sensors settings'));
    $this->assertText(t('Sensor example: "Installed modules"'));
    $this->assertLink(t('Configure'));
    $this->assertLink(t('Uninstall'), 0);
    $this->assertLink(t('Uninstall'), 1);
    $this->assertText(t('Drush integration - open up your console and type in # drush monitoring-sensor-config'));
  }

}
