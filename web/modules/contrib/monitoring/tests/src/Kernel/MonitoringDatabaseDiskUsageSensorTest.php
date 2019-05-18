<?php

namespace Drupal\Tests\monitoring\Kernel;

use Drupal\monitoring\Entity\SensorConfig;

/**
 * Kernel tests for the  monitoring database disk usage plugin.
 *
 * @group monitoring
 */
class MonitoringDatabaseDiskUsageSensorTest extends MonitoringUnitTestBase {

  /**
   * Tests the database disk usage sensor.
   */
  public function testDatabaseDiskUsage() {
    $sensor_config = SensorConfig::load('database_disk_usage');
    $sensor_config->thresholds['type'] = 'exceeds';
    $sensor_config->thresholds['warning'] = 80;
    $sensor_config->thresholds['critical'] = 100;
    $sensor_config->save();

    if (\Drupal::database()->databaseType() == 'mysql') {
      \Drupal::state()->set('monitoring.test_database_disk_usage', '');

      $sensor_result = $this->runSensor('database_disk_usage');
      $this->assertTrue($sensor_result->isCritical());
      $this->assertEquals($sensor_result->getMessage(), 'RuntimeException: The disk space usage is not available.');

      \Drupal::state()->set('monitoring.test_database_disk_usage', 50);

      $sensor_result = $this->runSensor('database_disk_usage');
      $this->assertTrue($sensor_result->isOk());
      $this->assertEquals($sensor_result->getMessage(), '50.00 mb');

      $verbose_output = $sensor_result->getVerboseOutput();
      $this->setRawContent(\Drupal::getContainer()->get('renderer')->renderPlain($verbose_output));
      $this->assertText('62.50%');
      $this->assertText('50.00%');

      \Drupal::state()->set('monitoring.test_database_disk_usage', 81);

      $sensor_result = $this->runSensor('database_disk_usage');
      $this->assertTrue($sensor_result->isWarning());
      $this->assertEquals($sensor_result->getMessage(), '81.00 mb, exceeds 80');

      $verbose_output = $sensor_result->getVerboseOutput();
      $this->setRawContent(\Drupal::getContainer()->get('renderer')->renderPlain($verbose_output));
      $this->assertText('101.25%');
      $this->assertText('81.00%');

      \Drupal::state()->set('monitoring.test_database_disk_usage', 101);

      $sensor_result = $this->runSensor('database_disk_usage');
      $this->assertTrue($sensor_result->isCritical());
      $this->assertEquals($sensor_result->getMessage(), '101.00 mb, exceeds 100');

      $verbose_output = $sensor_result->getVerboseOutput();
      $this->setRawContent(\Drupal::getContainer()->get('renderer')->renderPlain($verbose_output));
      $this->assertText('126.25%');
      $this->assertText('101.00%');
    }
    else {
      $sensor_result = $this->runSensor('database_disk_usage');
      $this->assertTrue($sensor_result->isCritical());
      $this->assertEquals($sensor_result->getMessage(), 'RuntimeException: The table information is only available for mysql databases.');
    }

  }

}
