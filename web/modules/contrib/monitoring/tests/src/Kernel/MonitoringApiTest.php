<?php
/**
 * @file
 * Contains \Drupal\Tests\monitoring\Kernel\MonitoringApiTest.
 */

namespace Drupal\Tests\monitoring\Kernel;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\DisabledSensorException;
use Drupal\monitoring\Sensor\NonExistingSensorException;
use Drupal\monitoring\Entity\SensorConfig;

/**
 * Tests for Monitoring API.
 *
 * @group monitoring
 */
class MonitoringApiTest extends MonitoringUnitTestBase {

  public static $modules = array('dblog');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('dblog', array('watchdog'));
  }

  /**
   * Test the base class if info is set and passed correctly.
   */
  public function testAPI() {

    // == Test sensor config. == //
    $sensor_config = SensorConfig::load('test_sensor_config');

    $this->assertEqual($sensor_config->getLabel(), 'Test sensor config');
    $this->assertEqual($sensor_config->getDescription(), 'To test correct sensor config hook implementation precedence.');
    // @todo - add tests for compulsory sensor config attributes.

    // @todo - add tests for default values of attributes.

    // @todo - override remaining attributes.

    // Define custom value label and number value type. In this setup the sensor
    // defined value label must be used.
    $sensor_config->value_label = 'Test label';
    $sensor_config->save();
    $this->assertEqual($sensor_config->getValueType(), 'number');
    $this->assertEqual($sensor_config->getValueLabel(), 'Test label');
    $this->assertTrue($sensor_config->isNumeric());
    $this->assertFalse($sensor_config->isBool());

    // Test value label provided by the monitoring_value_types().
    // Set the value type to one defined by the monitoring_value_types().
    $sensor_config->value_type = 'time_interval';
    $sensor_config->value_label = '';
    $sensor_config->save();
    $value_types = monitoring_value_types();
    $this->assertEqual($sensor_config->getValueLabel(), $value_types['time_interval']['value_label']);
    $this->assertTrue($sensor_config->isNumeric());
    $this->assertFalse($sensor_config->isBool());

    // Test value type without value label.
    $sensor_config->value_type = 'bool';
    $sensor_config->save();
    $this->assertEqual($sensor_config->getValueLabel(), NULL);
    $this->assertFalse($sensor_config->isNumeric());
    $this->assertTrue($sensor_config->isBool());
    // == Test basic sensor infrastructure - value, status and message. == //

    $test_sensor_result_data = array(
      'sensor_value' => 3,
      'sensor_status' => SensorResultInterface::STATUS_OK,
      'sensor_message' => 'All OK',
      'execution_time' => 1,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = $this->runSensor('test_sensor');

    $this->assertTrue($result->getExecutionTime() > 0);
    $this->assertEqual($result->getStatus(), $test_sensor_result_data['sensor_status']);
    $this->assertEqual($result->getMessage(), 'Value 3, ' . $test_sensor_result_data['sensor_message']);
    $this->assertEqual($result->getValue(), $test_sensor_result_data['sensor_value']);

    // == Test sensor result cache == //

    // Test cached result
    $result_cached = monitoring_sensor_run('test_sensor');
    $this->assertTrue($result_cached->isCached());
    $this->assertEqual($result_cached->getTimestamp(), REQUEST_TIME);
    $this->assertEqual($result_cached->getStatus(), $test_sensor_result_data['sensor_status']);
    $this->assertEqual($result_cached->getMessage(), 'Value 3, ' . $test_sensor_result_data['sensor_message']);
    $this->assertEqual($result_cached->getValue(), $test_sensor_result_data['sensor_value']);

    // Call a setter method to invalidate cache and reset values.
    $result_cached->setValue(5);
    $this->assertFalse($result_cached->isCached());

    // == Non-existing sensor error handling == //

    // Trying to fetch information for a non-existing sensor or trying to
    // execute such a sensor must throw an exception.
    try {
      monitoring_sensor_manager()->getSensorConfigByName('non_existing_sensor');
      $this->fail('Expected exception for non-existing sensor not thrown.');
    } catch (NonExistingSensorException $e) {
      $this->pass('Expected exception for non-existing sensor thrown.');
    }

    try {
      monitoring_sensor_run('non_existing_sensor');
      $this->fail('Expected exception for non-existing sensor not thrown.');
    } catch (NonExistingSensorException $e) {
      $this->pass('Expected exception for non-existing sensor thrown.');
    }

    // == Test disabled sensor. == //

    // Disable a sensor.
    monitoring_sensor_manager()->disableSensor('test_sensor');

    // Running a disabled sensor must throw an exception.
    try {
      monitoring_sensor_run('test_sensor');
      $this->fail('Expected exception for disabled sensor not thrown.');
    } catch (DisabledSensorException $e) {
      $this->pass('Expected exception for disabled sensor thrown.');
    }

    // Enable the sensor again.
    monitoring_sensor_manager()->enableSensor('test_sensor');
    $result = monitoring_sensor_run('test_sensor');
    $this->assertTrue($result instanceof SensorResultInterface);

    // == Test settings. == //

    // == inner_interval gives error statuses.

    // Test for OK values.
    $test_sensor_result_data = array(
      'sensor_value' => 11,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_inner', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_OK);
    $this->assertEqual($result->getMessage(), 'Value 11');

    $test_sensor_result_data = array(
      'sensor_value' => 0,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_inner', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_OK);
    $this->assertEqual($result->getMessage(), 'Value 0');

    // Test for warning values.
    $test_sensor_result_data = array(
      'sensor_value' => 7,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_inner', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_WARNING);
    $this->assertEqual($result->getMessage(), t('Value 7, violating the interval @expected', array('@expected' => '1 - 9')));

    $test_sensor_result_data = array(
      'sensor_value' => 2,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_inner', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_WARNING);
    $this->assertEqual($result->getMessage(), t('Value 2, violating the interval @expected', array('@expected' => '1 - 9')));

    // Test for critical values.
    $test_sensor_result_data = array(
      'sensor_value' => 5,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_inner', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_CRITICAL);
    $this->assertEqual($result->getMessage(), t('Value 5, violating the interval @expected', array('@expected' => '4 - 6')));

    $test_sensor_result_data = array(
      'sensor_value' => 5,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_inner', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_CRITICAL);
    $this->assertEqual($result->getMessage(), t('Value 5, violating the interval @expected', array('@expected' => '4 - 6')));

    // == outer_intervals give error statuses.

    // Test for ok values.
    $test_sensor_result_data = array(
      'sensor_value' => 75,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_outer', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_OK);
    $this->assertEqual($result->getMessage(), 'Value 75');

    $test_sensor_result_data = array(
      'sensor_value' => 71,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_outer', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_OK);
    $this->assertEqual($result->getMessage(), 'Value 71');

    // Test for warning values.
    $test_sensor_result_data = array(
      'sensor_value' => 69,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_outer', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_WARNING);
    $this->assertEqual($result->getMessage(), t('Value 69, outside the allowed interval @expected', array('@expected' => '70 - 80')));

    $test_sensor_result_data = array(
      'sensor_value' => 65,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_outer', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_WARNING);
    $this->assertEqual($result->getMessage(), t('Value 65, outside the allowed interval @expected', array('@expected' => '70 - 80')));

    // Test for critical values.
    $test_sensor_result_data = array(
      'sensor_value' => 55,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_outer', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_CRITICAL);
    $this->assertEqual($result->getMessage(), t('Value 55, outside the allowed interval @expected', array('@expected' => '60 - 90')));

    $test_sensor_result_data = array(
      'sensor_value' => 130,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_outer', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_CRITICAL);
    $this->assertEqual($result->getMessage(), t('Value 130, outside the allowed interval @expected', array('@expected' => '60 - 90')));

    // == Exceeds interval gives error statuses.

    $test_sensor_result_data = array(
      'sensor_value' => 4,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_exceeds', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_OK);
    $this->assertEqual($result->getMessage(), 'Value 4');

    $test_sensor_result_data = array(
      'sensor_value' => 6,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_exceeds', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_WARNING);
    $this->assertEqual($result->getMessage(), t('Value 6, exceeds @expected', array('@expected' => '5')));

    $test_sensor_result_data = array(
      'sensor_value' => 14,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_exceeds', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_CRITICAL);
    $this->assertEqual($result->getMessage(), t('Value 14, exceeds @expected', array('@expected' => '10')));

    // == Falls interval gives error statuses.

    $test_sensor_result_data = array(
      'sensor_value' => 12,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_falls', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_OK);
    $this->assertEqual($result->getMessage(), 'Value 12');

    $test_sensor_result_data = array(
      'sensor_value' => 9,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_falls', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_WARNING);
    $this->assertEqual($result->getMessage(), t('Value 9, falls below @expected', array('@expected' => '10')));

    $test_sensor_result_data = array(
      'sensor_value' => 3,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = monitoring_sensor_run('test_sensor_falls', TRUE);
    $this->assertEqual($result->getStatus(), SensorResultInterface::STATUS_CRITICAL);
    $this->assertEqual($result->getMessage(), t('Value 3, falls below @expected', array('@expected' => '5')));

    // Test the case when sensor value is not set.
    $test_sensor_result_data = array(
      'sensor_value' => NULL,
      'sensor_status' => SensorResultInterface::STATUS_CRITICAL,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $result = $this->runSensor('test_sensor');
    $this->assertNull($result->getValue());
  }

  /**
   * Test logging with different settings.
   */
  public function testLogging() {

    // First perform tests with the logging strategy in default mode - that is
    // "Log only on request or on status change".

    $test_sensor_result_data = array(
      'sensor_value' => 1,
      'sensor_message' => 'test message',
      'sensor_status' => SensorResultInterface::STATUS_OK,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $sensor = SensorConfig::load('test_sensor');
    $sensor->settings['result_logging'] = TRUE;
    $sensor->save();

    $this->runSensor('test_sensor');

    $logs = $this->loadSensorLog('test_sensor');
    $this->assertEqual(count($logs), 1);
    $log = array_shift($logs);
    $this->assertEqual($log->sensor_name->value, 'test_sensor');
    $this->assertEqual($log->sensor_status->value, SensorResultInterface::STATUS_OK);
    $this->assertEqual($log->sensor_value->value, 1);
    $this->assertEqual($log->sensor_message->value, 'Value 1, test message');

    // Set log_calls sensor settings to false - that should prevent logging.
    $sensor->settings['result_logging'] = FALSE;
    $sensor->save();
    /** @var \Drupal\monitoring\SensorRunner $runner */
    $runner = \Drupal::service('monitoring.sensor_runner');
    $runner->runSensors(array(SensorConfig::load('test_sensor')));
    $logs = $this->loadSensorLog('test_sensor');
    $this->assertEqual(count($logs), 1);

    // Now change the status - that should result in the call being logged.
    $test_sensor_result_data = array(
      'sensor_status' => SensorResultInterface::STATUS_WARNING,
    );
    \Drupal::state()->set('monitoring_test.sensor_result_data', $test_sensor_result_data);
    $this->runSensor('test_sensor');
    $logs = $this->loadSensorLog('test_sensor');
    $this->assertEqual(count($logs), 2);
    $log = array_pop($logs);
    $this->assertEqual($log->sensor_status->value, SensorResultInterface::STATUS_WARNING);

    // Set the logging strategy to "Log all events".
    $this->config('monitoring.settings')->set('sensor_call_logging', 'all')->save();
    // Running the sensor with 'result_logging' settings FALSE must record the call.
    $sensor->settings['result_logging'] = FALSE;
    $sensor->save();
    $this->container->set('monitoring.sensor_runner', NULL);
    $this->runSensor('test_sensor');
    $logs = $this->loadSensorLog('test_sensor');
    $this->assertEqual(count($logs), 3);

    // Set the logging strategy to "No logging".
    $this->config('monitoring.settings')->set('sensor_call_logging', 'none')->save();
    // Despite log_calls TRUE we should not log any call.
    $sensor->settings['result_logging'] = TRUE;
    $sensor->save();
    $this->container->set('monitoring.sensor_runner', NULL);
    $logs = $this->loadSensorLog('test_sensor');
    $this->runSensor('test_sensor');
    $this->assertEqual(count($logs), 3);

  }

  /**
   * Load sensor log data for a given sensor.
   *
   * @param $sensor_name
   *   The sensor name.
   *
   * @return array
   *   All log records of given sensor.
   */
  protected function loadSensorLog($sensor_name) {
    $result = \Drupal::entityQuery('monitoring_sensor_result')
      ->condition('sensor_name', $sensor_name)
      ->execute();
    return entity_load_multiple('monitoring_sensor_result', $result);
  }
}
