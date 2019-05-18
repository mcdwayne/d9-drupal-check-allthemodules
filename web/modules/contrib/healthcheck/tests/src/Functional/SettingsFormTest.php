<?php


namespace Drupal\Tests\healthcheck\Functional;


use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form.
 *
 * @group healthcheck
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * A user account with configure healthcheck permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The settings form path.
   *
   * @var string
   */
  protected $settings_form_path;

  public static $modules = [
    'user',
    'healthcheck',
    'healthcheck_findings_test',
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
   * Tests the categories form element.
   */
  public function testCategories() {
    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Get the settings form
    $this->drupalGet($this->settings_form_path);

    // Assure that we loaded the form.
    $session->statusCodeEquals(200);

    // Check if our target setting exists and that it's not checked.
    $session->checkboxNotChecked('edit-categories-testing');

    // Check 'testing' category.
    $edit = [
      'categories[testing]' => TRUE,
    ];
    $this->drupalPostForm($this->settings_form_path, $edit, 'Save configuration');

    // Reload the page.
    $this->drupalGet($this->settings_form_path);

    // Check the 'testing' category is now checked.
    $session->checkboxChecked('edit-categories-testing');
  }

  /**
   * Tests the Background Processing form element.
   */
  public function testBackgroundProcessing() {
    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Get the settings form
    $this->drupalGet($this->settings_form_path);

    // Assure that we loaded the form.
    $session->statusCodeEquals(200);

    // Check if our target setting exists and that it's currently not selected.
    $session->optionExists('run_every', 1);

    // Set to run every cron run.
    $edit = [
      'run_every' => 1,
    ];
    $this->drupalPostForm($this->settings_form_path, $edit, 'Save configuration');

    // Reload the page.
    $this->drupalGet($this->settings_form_path);

    /** @var NodeElement $omit */
    $run_every = $session->fieldExists('run_every')->getValue();

    // Check that we will not run every cron run.
    $this->assertTrue($run_every == 1, print_r($run_every, TRUE));
  }

  /**
   * Tests the Omit checks form element.
   */
  public function testOmitChecks() {
    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Get the settings form
    $this->drupalGet($this->settings_form_path);

    // Assure that we loaded the form.
    $session->statusCodeEquals(200);

    // Check if our target setting exists and that it's currently not selected.
    $session->optionExists('omit_checks[]', 'All findings');

    // Check 'testing' category.
    $edit = [
      'omit_checks[]' => [
        'all_findings'
      ],
    ];
    $this->drupalPostForm($this->settings_form_path, $edit, 'Save configuration');

    // Reload the page.
    $this->drupalGet($this->settings_form_path);

    /** @var NodeElement $omit */
    $omit = $session->fieldExists('omit_checks[]')->getValue();

    // Check that All Findings is now omitted.
    $this->assertTrue(array_search('all_findings', $omit) !== FALSE, 'All findings check not omitted!');
  }

}