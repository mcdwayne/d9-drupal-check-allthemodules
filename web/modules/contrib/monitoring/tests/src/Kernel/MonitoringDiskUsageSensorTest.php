<?php

namespace Drupal\Tests\monitoring\Kernel;

use Drupal\monitoring\Entity\SensorConfig;

/**
 * Kernel tests for the  monitoring disk usage plugin.
 *
 * @group monitoring
 */
class MonitoringDiskUsageSensorTest extends MonitoringUnitTestBase {

  /**
   * Test the disk usage sensor plugin.
   */
  public function testDiskUsageSensorPlugin() {
    $sensor = SensorConfig::create([
      'id' => 'disk_usage_test',
      'label' => 'Public files disk usage',
      'plugin_id' => 'disk_usage',
      'caching_time' => 86400,
      'value_type' => 'number',
      'value_label' => '%',
      'thresholds' => [
        'type' => 'exceeds',
        'warning' => 80,
        'critical' => 95,
      ],
      'settings' => [
        'directory' => 'public://',
      ],
    ]);
    $sensor->save();

    \Drupal::state()->set('monitoring.test_disk_usage', [
      'used_space_percent' => '40.00',
      'total_space' => '100.00 GB',
      'used_space' => '40.00 GB',
    ]);
    $sensor_result = $this->runSensor('disk_usage_test');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals($sensor_result->getMessage(), '40.00 %, 40.00 GB used of 100.00 GB available.');

    \Drupal::state()->set('monitoring.test_disk_usage', [
      'used_space_percent' => '81.00',
      'total_space' => '100.00 GB',
      'used_space' => '81.00 GB',
    ]);
    $sensor_result = $this->runSensor('disk_usage_test');
    $this->assertTrue($sensor_result->isWarning());
    $this->assertEquals($sensor_result->getMessage(), '81.00 %, exceeds 80, 81.00 GB used of 100.00 GB available.');

    \Drupal::state()->set('monitoring.test_disk_usage', [
      'used_space_percent' => '96.00',
      'total_space' => '100.00 GB',
      'used_space' => '96.00 GB',
    ]);
    $sensor_result = $this->runSensor('disk_usage_test');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getMessage(), '96.00 %, exceeds 95, 96.00 GB used of 100.00 GB available.');

    // Set invalid directory.
    $sensor->set('settings', [
      'directory' => 'publicccc://',
    ]);
    $sensor->save();
    \Drupal::state()->delete('monitoring.test_disk_usage');
    $sensor_result = $this->runSensor('disk_usage_test');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getMessage(), 'RuntimeException: Invalid directory.');
  }

}
