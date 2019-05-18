<?php
/**
 * @file
 * Contains \Drupal\Tests\monitoring\Kernel\MonitoringPastTest.
 */

namespace Drupal\Tests\monitoring\Kernel;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Tests for the past sensors in monitoring.
 *
 * @group monitoring
 * @dependencies past_db
 */
class MonitoringPastTest extends MonitoringUnitTestBase {

  public static $modules = array('views', 'past', 'past_db', 'options');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Install the past entities and tables.
    $this->installEntitySchema('past_event');
    $this->installSchema('past_db', array('past_event_argument', 'past_event_data'));
    $this->installSchema('system', ['router']);
    $this->installConfig(['system']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the sensors that monitors past events.
   */
  public function testPastSensors() {

    // Creates dummy events for testing.
    $this->createEvents();

    // Run each sensor and test output.
    $result = $this->runSensor('past_db_critical');
    $this->assertEqual($result->getMessage(), '3 events in 1 day');

    $result = $this->runSensor('past_db_debug');
    $this->assertEqual($result->getMessage(), '2 events in 1 day');

    $result = $this->runSensor('past_db_emergency');
    $this->assertEqual($result->getMessage(), '3 events in 1 day');

    $result = $this->runSensor('past_db_error');
    $this->assertEqual($result->getMessage(), '3 events in 1 day');

    $result = $this->runSensor('past_db_info');
    $this->assertEqual($result->getMessage(), '2 events in 1 day');

    $result = $this->runSensor('past_db_notice');
    $this->assertEqual($result->getMessage(), '2 events in 1 day');

    $result = $this->runSensor('past_db_warning');
    $this->assertEqual($result->getMessage(), '3 events in 1 day');
  }

  /**
   * Creates some sample events.
   */
  protected function createEvents($count = 20) {
    // Set some for log creation.
    $machine_name = 'machine name';
    $severities = RfcLogLevel::getLevels();
    $severities_codes = array_keys($severities);
    $severities_count = count($severities);
    $event_desc = 'message #';

    // Prepare some logs.
    for ($i = 0; $i <= $count; $i++) {
      $event = past_event_create('past_db', $machine_name, $event_desc . ($i + 1), ['timestamp' => REQUEST_TIME]);
      $event->setReferer('http://example.com/test-referer');
      $event->setLocation('http://example.com/this-url-gets-heavy-long/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttest/seeme.htm');
      $event->addArgument('arg1', 'First Argument');
      $event->addArgument('arg2', new \stdClass());
      $event->addArgument('arg3', FALSE);
      $event->setSeverity($severities_codes[$i % $severities_count]);
      $event->save();
    }
  }

}
