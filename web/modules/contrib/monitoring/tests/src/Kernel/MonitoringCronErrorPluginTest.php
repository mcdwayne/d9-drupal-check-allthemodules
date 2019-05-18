<?php

namespace Drupal\Tests\monitoring\Kernel;

use Drupal\monitoring\Controller\RebuildSensorList;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\ultimate_cron\Entity\CronJob;

/**
 * Kernel test for ultimate cron errors sensor plugin.
 *
 * @group monitoring
 */
class MonitoringCronErrorPluginTest extends MonitoringUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'ultimate_cron', 'ultimate_cron_logger_test');

  /**
   * {@inheritdoc}
   */
  public function setup() {
    parent::setUp();

    // Install schema to create the required tables.
    $this->installSchema('ultimate_cron', [
      'ultimate_cron_log',
      'ultimate_cron_lock'
    ]);

    // Create cron jobs to be run.
    \Drupal::service('ultimate_cron.discovery')->discoverCronJobs();
    // Disable system_cron job, so it does not create an additional error.
    $job = CronJob::load('system_cron');
    $job->disable();
    $job->save();
  }

  /**
   * Tests ultimate cron errors sensor.
   */
  public function testUltimateCronErrorsSensorPlugin() {

    // Run sensor and make sure there are no errors.
    $result = $this->runSensor('ultimate_cron_errors');
    $this->assertEquals(0, $result->getValue());

    // Set 1 error and run cron.
    \Drupal::state()->set('ultimate_cron_logger_test_cron_action', 'exception');
    \Drupal::service('cron')->run();

    // Run sensor and assert an error.
    $result = $this->runSensor('ultimate_cron_errors');
    $this->assertEquals(1, $result->getValue());

    // Assert verbose output and error message.
    $verbose_output = $result->getVerboseOutput()['log_entries']['#rows'][0];
    $this->assertTrue($result->isOk());
    $this->assertEquals('Default cron handler (Ultimate Cron Logger Test)', $verbose_output['name']);
    $this->assertContains('Test cron exception', $verbose_output['message']);
  }
}
