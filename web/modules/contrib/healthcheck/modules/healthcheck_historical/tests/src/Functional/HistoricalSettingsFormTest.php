<?php


namespace Drupal\Tests\healthcheck_historical\Functional;


use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form.
 *
 * @group healthcheck_historical
 */
class HistoricalSettingsFormTest extends BrowserTestBase {

  protected $account;

  protected $settings_form_path;

  public static $modules = [
    'user',
    'views',
    'healthcheck',
    'healthcheck_historical',
  ];

  protected function setUp() {
    parent::setUp();

    // Get the settings form path.
    $this->settings_form_path = Url::fromRoute('healthcheck.healthcheck_settings_form');

    // Create a healthcheck admin user.
    $this->account = $this->drupalCreateUser([
      'configure healthcheck',
    ]);
  }

  /**
   * Tests the critical email form configuration.
   */
  public function testSettings() {
    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Get the settings form
    $this->drupalGet($this->settings_form_path);

    // Assure that we loaded the form.
    $session->statusCodeEquals(200);

    // Test that fieldset is there.
    $session->pageTextContains('Historical storage');

    $session->optionExists('keep_reports_for', -1);

    // Update the settings.
    $edit = [
      'keep_reports_for' => -1,
    ];
    $this->drupalPostForm($this->settings_form_path, $edit, 'Save configuration');

    // Reload the page.
    $this->drupalGet($this->settings_form_path);

    /** @var NodeElement $omit */
    $keep_reports_for = $session->fieldExists('keep_reports_for')->getValue();

    // Check that we will not run every cron run.
    $this->assertTrue($keep_reports_for == -1, print_r($keep_reports_for, TRUE));
  }

}
