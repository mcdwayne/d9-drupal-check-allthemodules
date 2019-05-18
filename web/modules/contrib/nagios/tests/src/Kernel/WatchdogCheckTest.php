<?php

namespace Drupal\Tests\nagios\Kernel;

use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\nagios\Controller\StatuspageController;

/**
 * Tests the functionality to report dblog/watchdog entries.
 *
 * @group nagios
 */
class WatchdogCheckTest extends EntityKernelTestBase {

  use LoggerChannelTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['nagios', 'user', 'dblog'];

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig('nagios');
    $this->installSchema('dblog', 'watchdog');
    StatuspageController::setNagiosStatusConstants();
  }

  public function testEmptyWatchdog() {
    $this->assertAllGreen();
  }

  private function assertAllGreen() {
    $expected = [
      'status' => 0,
      'type' => 'state',
      'text' => '',
    ];
    self::expectWatchdog($expected);
  }

  private static function expectWatchdog($expected) {
    $actual = nagios_check_watchdog()['data'];
    $actual['text'] = preg_replace('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} /', '', $actual['text']);
    self::assertEquals($expected, $actual);
  }

  public function testWithLevelWarning() {
    $this->getLogger('test')->info('info');
    $this->assertAllGreen();

    $this->getLogger('test')->warning('warning');
    $expected = [
      'status' => NAGIOS_STATUS_WARNING,
      'type' => 'state',
      'text' => 'test warning',
    ];
    self::expectWatchdog($expected);

    $this->getLogger('test')->error('error');
    $expected = [
      'status' => NAGIOS_STATUS_CRITICAL,
      'type' => 'state',
      'text' => 'test error, test warning',
    ];
    self::expectWatchdog($expected);
  }

  public function testOrder() {
    $this->getLogger('test')->warning('warning 1');
    sleep(2);
    $this->getLogger('test')->warning('warning 2');
    $expected = [
      'status' => NAGIOS_STATUS_WARNING,
      'type' => 'state',
      'text' => 'test warning 2, test warning 1',
    ];
    self::expectWatchdog($expected);
  }

  public function testWithLevelError() {
    $config = \Drupal::configFactory()->getEditable('nagios.settings');
    $config->set('nagios.min_report_severity', NAGIOS_STATUS_CRITICAL);
    $config->save();

    $this->getLogger('test')->info('info');
    $this->assertAllGreen();

    $this->getLogger('test')->warning('warning');
    $this->assertAllGreen();

    $this->getLogger('test')->error('error');
    $expected = [
      'status' => NAGIOS_STATUS_CRITICAL,
      'type' => 'state',
      'text' => 'test error',
    ];
    self::expectWatchdog($expected);
  }
}

