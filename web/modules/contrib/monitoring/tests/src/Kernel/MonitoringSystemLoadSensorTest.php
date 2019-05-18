<?php

namespace Drupal\Tests\monitoring\Kernel;

use Drupal\monitoring\Entity\SensorConfig;

/**
 * Kernel tests for the monitoring system load plugin.
 *
 * @group monitoring
 */
class MonitoringSystemLoadSensorTest extends MonitoringUnitTestBase {

  /**
   * Tests the system loa average sensor.
   */
  public function testSystemLoadSensorPlugin() {
    // Default sensor settings with set available space for testing.
    $sensor = SensorConfig::create([
      'id' => 'system_load_test',
      'label' => 'System load',
      'plugin_id' => 'system_load',
      'value_label' => '% Average',
      'caching_time' => 86400,
      'value_type' => 'number',
      'thresholds' => [
        'type' => 'exceeds',
        'warning' => 80,
        'critical' => 100,
      ],
      'settings' => [
        'average_monitored' => '1',
      ],
    ]);
    $sensor->save();

    $this->container->get('state')->set('monitoring.test_load_average', [0.6, 0.6, 0.6]);
    // Check if the sensor status is OK.
    $sensor_result = $this->runSensor('system_load_test');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals($sensor_result->getValue(), 60);
    $this->assertEquals($sensor_result->getMessage(), '60 % average, 0.6, 0.6, 0.6');

    $this->container->get('state')->set('monitoring.test_load_average', [0.9, 0.9, 0.9]);
    // Check if the sensor status is Warning.
    $sensor_result = $this->runSensor('system_load_test');
    $this->assertTrue($sensor_result->isWarning());
    $this->assertEquals($sensor_result->getValue(), 90);
    $this->assertEquals($sensor_result->getMessage(), '90 % average, exceeds 80, 0.9, 0.9, 0.9');
    $this->container->get('state')->set('monitoring.test_load_average', [1.2, 1.2, 1.2]);
    // Check if the sensor status is Critical.
    $sensor_result = $this->runSensor('system_load_test');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getValue(), 120);
    $this->assertEquals($sensor_result->getMessage(), '120 % average, exceeds 100, 1.2, 1.2, 1.2');
  }

}
