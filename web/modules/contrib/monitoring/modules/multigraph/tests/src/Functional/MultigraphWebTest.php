<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Tests\MultigraphWebTest
 */

namespace Drupal\Tests\monitoring_multigraph\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Multigraph forms (add/edit/delete).
 *
 * @group monitoring
 */
class MultigraphWebTest extends BrowserTestBase {

  /**
   * User object.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = [
    'dblog',
    'node',
    'monitoring',
    'monitoring_multigraph',
  ];

  /**
   * Configures test base and executes test cases.
   */
  public function testMultigraphForm() {
    // Create and log in our user.
    $this->adminUser = $this->drupalCreateUser([
      'administer monitoring',
    ]);

    $this->drupalLogin($this->adminUser);

    $this->doTestMultigraphAdd();
    $this->doTestMultigraphEdit();
    $this->doTestMultigraphDelete();
  }

  /**
   * Tests multigraph creation.
   */
  public function doTestMultigraphAdd() {
    // Add a few sensors.
    $values = [
      'label' => $this->randomString(),
      'id' => 'multigraph_123',
      'description' => $this->randomString(),
      'sensor_add_select' => 'dblog_404',
    ];
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/add', $values, t('Add sensor'));
    $this->assertText(t('Sensor "Page not found errors" added. You have unsaved changes.'));

    $this->drupalPostForm(NULL, [
      'sensor_add_select' => 'user_failed_logins',
    ], t('Add sensor'));
    $this->assertText(t('Sensor "Failed user logins" added. You have unsaved changes.'));

    $this->drupalPostForm(NULL, [
      'sensor_add_select' => 'user_successful_logins',
    ], t('Add sensor'));
    $this->assertText(t('Sensor "Successful user logins" added. You have unsaved changes.'));

    // And last but not least, change all sensor label values and save form.
    $this->drupalPostForm(NULL, [
      'sensors[dblog_404][label]' => 'Page not found errors (test)',
      'sensors[user_failed_logins][label]' => 'Failed user logins (test)',
      'sensors[user_successful_logins][label]' => 'Successful user logins (test)',
    ], t('Save'));
    $this->assertText(t('Multigraph settings saved.'));
    $this->assertText('Page not found errors (test), Failed user logins (test), Successful user logins (test)');
  }

  /**
   * Tests multigraph editing.
   *
   * Tests all changeable input fields.
   */
  public function doTestMultigraphEdit() {
    // Go to multigraph overview and test editing pre-installed multigraph.
    $this->drupalGet('admin/config/system/monitoring/multigraphs');
    // Check label, description and sensors (before editing).
    $this->assertText('Watchdog severe entries');
    $this->assertText('Watchdog entries with severity Warning or higher');
    $this->assertText('404, Alert, Critical, Emergency, Error');

    // Edit.
    $this->drupalGet('admin/config/system/monitoring/multigraphs/watchdog_severe_entries');
    $this->assertText('Edit Multigraph');

    // Change label, description and add a sensor.
    $values = [
      'label' => 'Watchdog severe entries (test)',
      'description' => 'Watchdog entries with severity Warning or higher (test)',
      'sensor_add_select' => 'user_successful_logins',
    ];
    $this->drupalPostForm(NULL, $values, t('Add sensor'));
    $this->assertText('Sensor "Successful user logins" added. You have unsaved changes.');

    // Remove a sensor.
    $this->getSession()->getPage()->pressButton('remove_dblog_404');
    // (drupalPostAjaxForm() lets us target the button precisely.)
    $this->assertText(t('Sensor "Page not found errors" removed.  You have unsaved changes.'));
    $this->drupalPostForm(NULL, [], t('Save'));

    // Change weights and save form.
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/watchdog_severe_entries', [
      'sensors[user_successful_logins][weight]' => -2,
      'sensors[dblog_event_severity_error][weight]' => -1,
      'sensors[dblog_event_severity_critical][weight]' => 0,
      'sensors[dblog_event_severity_emergency][weight]' => 1,
      'sensors[dblog_event_severity_alert][weight]' => 2,
    ], t('Save'));
    $this->assertText(t('Multigraph settings saved.'));

    // Go back to multigraph overview and check changed values.
    $this->drupalGet('admin/config/system/monitoring/multigraphs');
    $this->assertText('Watchdog severe entries (test)');
    $this->assertText('Watchdog entries with severity Warning or higher (test)');
    $this->assertText('Successful user logins, Error, Critical, Emergency, Alert');
  }

  /**
   * Tests multigraph deletion.
   */
  public function doTestMultigraphDelete() {
    // Go to multigraph overview and check for pre-installed multigraph.
    $this->drupalGet('admin/config/system/monitoring/multigraphs');
    // Check label and description (before deleting).
    $this->assertText('Watchdog severe entries');
    $this->assertText('Watchdog entries with severity Warning or higher');

    // Delete.
    $this->drupalPostForm('admin/config/system/monitoring/multigraphs/watchdog_severe_entries/delete', [], t('Delete'));
    $this->assertText('The Watchdog severe entries (test) multigraph has been deleted');

    // Go back to multigraph overview and check that multigraph is deleted.
    $this->drupalGet('admin/config/system/monitoring/multigraphs');
    $this->assertNoText('Watchdog severe entries');
    $this->assertNoText('Watchdog entries with severity Warning or higher');
  }
}
