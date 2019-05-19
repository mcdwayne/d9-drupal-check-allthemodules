<?php

/**
 * @file
 * Simplenews scheduler test functions.
 *
 * @ingroup simplenews_scheduler
 */

namespace Drupal\simplenews_scheduler\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Class SimplenewsSchedulerWebTestBase
 */
abstract class SimplenewsSchedulerWebTestBase extends WebTestBase {

  public static $modules = array('simplenews_scheduler');

  /**
   * The Simplenews scheduler settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Overrides DrupalWebTestCase::setUp().
   *
   * @param $modules
   *   Additional modules to enable for the test. simplenews_scheduler and
   *   the dependencies are always enabled.
   */
  function setUp($modules = array()) {
    parent::setUp();
    // Set the site timezone to something visibly different from UTC, which
    // has daylight saving changes.
    $date_config = $this->config('system.date');
    $date_config->set('timezone.default', 'Europe/Kiev');
    $date_config->save();

    date_default_timezone_set(drupal_get_user_timezone());
  }

}
