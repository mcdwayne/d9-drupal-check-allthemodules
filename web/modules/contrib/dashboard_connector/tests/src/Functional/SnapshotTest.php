<?php

namespace Drupal\Tests\dashboard_connector\Functional;

use Drupal\dashboard_connector\Dashboard;
use Drupal\Tests\BrowserTestBase;

/**
 * The the snapshot building.
 *
 * @group dashboard_connector
 */
class SnapshotTest extends BrowserTestBase {

  /**
   * Modules to enable. Specifically views_ui so we get a warning.
   *
   * @var array
   */
  public static $modules = [
    'dashboard_connector',
    'views',
    'views_ui',
  ];

  /**
   * Build a snapshot against the active site and assert the result.
   */
  public function testBuildSnapshot() {
    $snapshot = Dashboard::snapshotBuilder()->buildSnapshot();

    // We diff the known checks with the generate checks to ensure they exist.
    foreach ($this->getKnownChecks() as $module_name => $known_check) {
      $check_exists = FALSE;
      foreach ($snapshot['checks'] as $check) {
        // The check exists, now assert they're identical.
        if ($check['name'] === $module_name) {
          $this->assertEquals($check, $known_check);
          $check_exists = TRUE;
          break;
        }
      }
      // If the check did not exist at all then fail the build.
      if (!$check_exists) {
        $this->fail(sprintf('The %s check did not exist', $module_name));
      }
    }
  }

  /**
   * An array of known checks that will appear agains the test profile.
   *
   * @return array
   *   The known checks.
   */
  protected function getKnownChecks() {
    return [
      'views_ui' => [
        'type' => 'module disabled',
        'name' => 'views_ui',
        'description' => 'views_ui module is enabled',
        'alert_level' => 'warning',
      ],
    ];
  }

}
