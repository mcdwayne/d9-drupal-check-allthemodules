<?php

namespace Drupal\Tests\dmt\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\dmt\Entity\Module;
use Drupal\dmt\Entity\WeeklyUsage;

/**
 * Test weekly usage content entities.
 *
 * @group dmt
 */
class WeeklyUsageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dmt'];

  /**
   * Tests that the weekly usage entity can be created.
   */
  public function testWeeklyUsageCRUD() {
    $module = Module::create(['name' => 'my_test_module']);
    $module->save();
    $weekly_usage = WeeklyUsage::create([]);
    $weekly_usage->setModule($module);
    $weekly_usage->setInstallCount(100);
    $date = \Drupal\Core\Datetime\DrupalDateTime::createFromTimestamp(time());
    $weekly_usage->setDate($date);
    $weekly_usage->save();
    $this->assertEqual($weekly_usage->getInstallCount(), 100);
    $this->assertEqual($weekly_usage->getDate()->format(DATETIME_DATE_STORAGE_FORMAT), $date->format(DATETIME_DATE_STORAGE_FORMAT));
  }

}
