<?php

namespace Drupal\Tests\monitoring_mail\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\Tests\monitoring\Kernel\MonitoringUnitTestBase;

/**
 * Kernel tests for the mail pieces of monitoring.
 *
 * @group monitoring
 */
class MonitoringMailKernelTest extends MonitoringUnitTestBase {

  use AssertMailTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'dblog',
    'monitoring_mail',
    'automated_cron'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('dblog', ['watchdog']);
    $this->installConfig([
      'system',
      'monitoring_mail',
    ]);

    $this->config('system.site')
      ->set('name', 'Example')
      ->save();

    \Drupal::moduleHandler()->loadAllIncludes('install');
    monitoring_install();

    // Set the site mail.
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('mail', 'site_mail@example.com')
      ->save();

    // Allow running all enabled sensors.
    \Drupal::configFactory()
      ->getEditable('monitoring.settings')
      ->set('cron_run_sensors', TRUE)
      ->save();
    // Set a valid email address for this test.
    \Drupal::configFactory()
      ->getEditable('monitoring_mail.settings')
      ->set('mail', 'mail@example.com')
      ->save();
  }

  /**
   * Tests transition mail on sensor runs.
   */
  public function testTransitionMailOnSensorRuns() {
    $sensor_runner = \Drupal::service('monitoring.sensor_runner');

    // A backtrace is logged for CRITICAL sensor status by default.
    $sensorConfig = SensorConfig::load('test_sensor_falls');

    // Check that there are no emails sent.
    $this->assertEmpty($this->getMails());

    // Run a sensor that is CRITICAL and check its corresponding mail.
    /** @var \Drupal\monitoring\Result\SensorResult $result */
    $result = $sensor_runner->runSensors([$sensorConfig])[0];
    $this->assertEquals('CRITICAL', $result->getStatus());
    $this->assertEquals(1, count($this->getMails()));
    // Check if the 'mail header Message-ID' contains sensor_id and host name.
    $mails = $this->getMails();
    $this->assertContains('test_sensor_falls', $mails[0]['headers']['Message-ID']);
    $this->assertContains(\Drupal::request()->getHost(), $mails[0]['headers']['Message-ID']);
    // Check the 'mail header From' value.
    $this->assertEquals('MONITORING Example <site_mail@example.com>', $mails[0]['headers']['From']);

    // Run the same sensor again and make sure no additional mail is sent,
    // because its status has not been changed.
    $result = $sensor_runner->runSensors([$sensorConfig])[0];
    $this->assertEquals('CRITICAL', $result->getStatus());
    $this->assertEquals(1, count($this->getMails()));

    // Change sensor threshold settings so that the sensor switches to WARNING.
    $sensorConfig->thresholds['critical'] = 0;
    // Set severities to log also WARNING sensor status.
    \Drupal::configFactory()
      ->getEditable('monitoring_mail.settings')
      ->set('severities', [
        SensorResultInterface::STATUS_CRITICAL,
        SensorResultInterface::STATUS_WARNING,
      ])
      ->save();

    // Run it again, another mail is sent because the status has been changed.
    $result = $sensor_runner->runSensors([$sensorConfig])[0];
    $this->assertEquals('WARNING', $result->getStatus());
    $this->assertEquals(2, count($this->getMails()));
    // Check if the 'mail header References' contains the previous Message-ID.
    $mails = $this->getMails();
    $this->assertEquals($mails[0]['headers']['Message-ID'], $mails[1]['headers']['References']);

    // Change sensor threshold settings so that the sensor switches to OK.
    $sensorConfig->thresholds['warning'] = 0;

    // Run it again, make sure that no additional mail is sent, because its
    // changed status is not in the mail severities.
    $result = $sensor_runner->runSensors([$sensorConfig])[0];
    $this->assertEquals('OK', $result->getStatus());
    $this->assertEquals(2, count($this->getMails()));
  }
}
