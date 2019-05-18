<?php

namespace Drupal\Tests\monitoring\Kernel;
use Drupal\Core\Database\Database;

/**
 * Tests the Redirect 404 sensor plugin.
 *
 * @group monitoring
 */
class MonitoringRedirect404SensorTest extends MonitoringUnitTestBase {

  /**
   * Modules to be enabled.
   */
  public static $modules = [
    'redirect_404',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('redirect_404', 'redirect_404');
    $this->installConfig(array('system'));
  }

  /**
   * Tests the Redirect 404 sensor plugin.
   */
  public function testRedirect404Sensor() {
    $sensor_result = $this->runSensor('redirect_404');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals('0', $sensor_result->getValue());
    $database = Database::getConnection('default');
    // Add a 404 request to an unresolved path.
    $database->insert('redirect_404')->fields([
      'path' => '/non-existing-path',
      'langcode' => 'en',
      'count' => '3',
      'timestamp' => time(),
      'resolved' => 0,
    ])->execute();

    // Checks the sensor result.
    $sensor_result = $this->runSensor('redirect_404');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals('3', $sensor_result->getValue());
    $this->assertContains('/non-existing-path', $sensor_result->getMessage());

    // Add a 404 request to a resolved path.
    $database->insert('redirect_404')->fields([
      'path' => '/non-existing-path-2',
      'langcode' => 'en',
      'count' => '4',
      'timestamp' => time(),
      'resolved' => 1,
    ])->execute();

    // Result should be the same, since the resolved requests are not tracked
    // by the sensor.
    $sensor_result = $this->runSensor('redirect_404');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals('3', $sensor_result->getValue());
    $this->assertContains('/non-existing-path', $sensor_result->getMessage());

    // Add a 404 request to an unresolved path, 2 days old.
    $database->insert('redirect_404')->fields([
      'path' => '/non-existing-path-3',
      'langcode' => 'en',
      'count' => '4',
      'timestamp' => time() - 172800,
      'resolved' => 0,
    ])->execute();

    // Result should be the same, since the requests older than 1 day are not
    // tracked by the sensor.
    $sensor_result = $this->runSensor('redirect_404');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals('3', $sensor_result->getValue());
    $this->assertContains('/non-existing-path', $sensor_result->getMessage());

    // Add a 404 request to an unresolved path.
    $database->insert('redirect_404')->fields([
      'path' => '/non-existing-path-4',
      'langcode' => 'en',
      'count' => '4',
      'timestamp' => time(),
      'resolved' => 0,
    ])->execute();

    // Checks the new sensor result and verbose output.
    $sensor_result = $this->runSensor('redirect_404');
    $this->assertTrue($sensor_result->isOk());
    $this->assertEquals('4', $sensor_result->getValue());
    $this->assertContains('/non-existing-path-4', $sensor_result->getMessage());
    $this->assertEquals('/non-existing-path-4', $sensor_result->getVerboseOutput()['verbose_sensor_result']['#rows'][0]['path']);
    $this->assertEquals('/non-existing-path', $sensor_result->getVerboseOutput()['verbose_sensor_result']['#rows'][1]['path']);
    $this->assertEquals('4', $sensor_result->getVerboseOutput()['verbose_sensor_result']['#rows'][0]['count']);
    $this->assertEquals('3', $sensor_result->getVerboseOutput()['verbose_sensor_result']['#rows'][1]['count']);
  }
}
