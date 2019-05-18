<?php

namespace Drupal\Tests\nagios\Kernel;

use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\nagios\Controller\StatuspageController;

/**
 * Tests the functionality to monitor cron.
 *
 * @group nagios
 */
class NagiosCheckTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['nagios', 'user'];

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig('nagios');
    StatuspageController::setNagiosStatusConstants();
  }

  public function testElysiaCronCheck() {
    $conn = Database::getConnection();
    $conn->query('CREATE TABLE {elysia_cron} (last_aborted int(11), name varchar(8), last_abort_function varchar(8))');
    $status = nagios_check_elysia_cron()['data']['status'];
    self::assertEquals(NAGIOS_STATUS_OK, $status);

    $conn->query("INSERT INTO {elysia_cron} VALUES (1, 'toad', 'toadcron')");
    $status = nagios_check_elysia_cron()['data']['status'];
    self::assertEquals(NAGIOS_STATUS_CRITICAL, $status);

    $conn->query('DROP TABLE {elysia_cron}');
  }

  public function testCronCheck() {
    // set last run to an old date
    \Drupal::state()->set('system.cron_last', 0);

    // run check function, expect warning
    $result1 = nagios_check_cron();
    self::assertSame(2, $result1['data']['status'], "Check critical response");

    // run cron
    /** @var \Drupal\Core\CronInterface $cron */
    $cron = \Drupal::service('cron');
    $cron->run();

    // run check function, expect no warning
    $result2 = nagios_check_cron();
    self::assertSame(0, $result2['data']['status'], "Check ok response");
  }

  public function testStatuspage() {
    $statuspage_controller = new StatuspageController();
    $_SERVER['HTTP_USER_AGENT'] = 'Test';
    self::assertContains(
      "nagios=UNKNOWN, DRUPAL:UNKNOWN=Unauthorized |",
      $statuspage_controller->content()->getContent());

    $_SERVER['HTTP_USER_AGENT'] = 'Nagios';
    self::assertContains(
      "nagios=OK,",
      $statuspage_controller->content()->getContent());

    $config = \Drupal::configFactory()->getEditable('nagios.settings');
    $config->set('nagios.statuspage.getparam', TRUE);
    $config->save();
    $_SERVER['HTTP_USER_AGENT'] = 'Test';
    self::assertContains(
      "nagios=UNKNOWN, DRUPAL:UNKNOWN=Unauthorized |",
      $statuspage_controller->content()->getContent());

    $_GET['unique_id'] = 'Nagios';
    self::assertContains(
      "nagios=OK,",
      $statuspage_controller->content()->getContent());

    self::assertInstanceOf(AccessResultNeutral::class, $statuspage_controller->access());
    self::assertFalse($statuspage_controller->access()->isAllowed());

    $config->set('nagios.statuspage.enabled', TRUE);
    $config->save();
    self::assertTrue($statuspage_controller->access()->isAllowed());
  }

  public function testWatchdogIfNotEnabled() {
    $expected = [
      'status' => 3,
      'type' => 'state',
      'text' => 'Unable to SELECT FROM {watchdog}',
    ];
    self::assertEquals($expected, nagios_check_watchdog()['data']);
  }
}

