<?php

namespace Drupal\Tests\monitoring\Functional;

use Drupal\monitoring\Entity\SensorConfig;

/**
 * Tests the updating of the sensor list.
 *
 * @group monitoring
 */
class MonitoringRebuildTest extends MonitoringTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'ultimate_cron', 'update', 'monitoring_test');

  /**
   * Tests creating non-addable sensors.
   *
   * @see \Drupal\monitoring\Controller\RebuildSensorList::rebuild()
   */
  public function testRebuildNonAddable() {
    // Create and login user with permission to view monitoring reports.
    $test_user = $this->drupalCreateUser([
      'monitoring reports',
      'administer monitoring',
    ]);
    $this->drupalLogin($test_user);

    // Delete sensors from install and optional directory.
    SensorConfig::load('twig_debug_mode')->delete();
    SensorConfig::load('ultimate_cron_errors')->delete();
    SensorConfig::load('update_core')->delete();
    $sensor = SensorConfig::load('core_requirements_monitoring_test');
    $this->assertNotNull($sensor);

    $result = $this->runSensor('core_requirements_monitoring_test');
    $this->assertTrue($result->isOk());

    $result = $this->runSensor('monitoring_disappeared_sensors');
    $this->assertTrue($result->isOk());

    // Disable the requirements hook.
    \Drupal::state()->set('monitoring_test_requirements_enabled', FALSE);

    // Rebuild and make sure they are created again.
    $this->drupalGet('/admin/config/system/monitoring/sensors');
    $this->clickLink('Rebuild sensor list');
    $this->assertText('The sensor Ultimate cron errors has been created.');
    $this->assertText('The sensor Twig debug mode has been created.');
    $this->assertNotNull(SensorConfig::load('twig_debug_mode'));
    $this->assertNotNull(SensorConfig::load('ultimate_cron_errors'));
    $this->assertNotNull(SensorConfig::load('update_core'));

    // Make sure the requirements sensor was removed
    $this->assertNull(SensorConfig::load('core_requirements_monitoring_test'));
    $result = $this->runSensor('monitoring_disappeared_sensors');
    $this->assertTrue($result->isOk());
  }

}
