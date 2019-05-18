<?php

namespace Drupal\Tests\monitoring\Kernel;

/**
 * Kernel tests for system memory sensor plugin.
 *
 * @group monitoring
 */
class MonitoringSystemMemoryPluginTest extends MonitoringUnitTestBase {

  /**
   * Tests the system memory sensor plugin.
   */
  public function testSystemMemorySensorPlugin() {
    // Sets data that will represent the memory information.
    $data = "MemTotal:  20000 kB\nMemAvailable:  10000 kB";
    \Drupal::state()->set('monitoring.test_meminfo', $data);
    $sensor_manager = monitoring_sensor_manager();
    $sensor_config = $sensor_manager->getSensorConfigByName('system_memory');
    $sensor_config->set('status', TRUE);
    $sensor_config->save();

    // Run sensor and assert message and verbose output.
    $sensor_result = $this->runSensor('system_memory');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals($sensor_result->getMessage(), '50 % free memory');
    $this->assertEquals($sensor_result->getVerboseOutput()['memory_info']['#rows'][0]['type'], 'MemTotal');
    $this->assertEquals($sensor_result->getVerboseOutput()['memory_info']['#rows'][0]['memory'], '20000 kB');

    // Sets incorrect data that should trigger an error message to the sensor.
    $data = 'No memory information provided.';
    \Drupal::state()->set('monitoring.test_meminfo', $data);
    $sensor_result = $this->runSensor('system_memory');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getMessage(), 'This sensor is not supported by your system. It is based on memory information from /proc/meminfo, only provided by UNIX-like systems.');

    // Sets NULL data that should trigger an error message to the sensor.
    \Drupal::state()->set('monitoring.test_meminfo', '');
    $sensor_result = $this->runSensor('system_memory');
    $this->assertTrue($sensor_result->isCritical());
    $this->assertEquals($sensor_result->getMessage(), 'This sensor is not supported by your system. It is based on memory information from /proc/meminfo, only provided by UNIX-like systems.');
    $this->assertNull($sensor_result->getVerboseOutput());
  }
}
