<?php


namespace Drupal\Tests\healthcheck_email\Functional;


use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form.
 *
 * @group healthcheck_email
 */
class EmailSettingsFormTest extends BrowserTestBase {

  protected $account;

  protected $settings_form_path;

  public static $modules = [
    'user',
    'healthcheck',
    'healthcheck_email',
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
  public function testCritical() {
    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Get the settings form
    $this->drupalGet($this->settings_form_path);

    // Assure that we loaded the form.
    $session->statusCodeEquals(200);

    // Test that fieldset is there.
    $session->pageTextContains('Email notifications for critical findings');

    // And that the critical emails are not enabled.
    $session->checkboxNotChecked('email_critical_enabled');

    // And that other email fields exists.
    $session->fieldExists('email_critical_to');
    $session->fieldExists('email_critical_subject');
    $session->fieldExists('email_critical_body');

    // Update the settings.
    $edit = [
      'email_critical_enabled' => 1,
      'email_critical_to' => 'ops@example.com',
      'email_critical_subject' => 'Test Critical!',
      'email_critical_body' => 'Bad stuff happened.',
    ];
    $this->drupalPostForm($this->settings_form_path, $edit, 'Save configuration');

    // Reload the page.
    $this->drupalGet($this->settings_form_path);

    // Check the settings for change.
    $session->checkboxChecked('email_critical_enabled');
    $session->fieldValueEquals('email_critical_to', $edit['email_critical_to']);
    $session->fieldValueEquals('email_critical_subject', $edit['email_critical_subject']);
    $session->fieldValueEquals('email_critical_body', $edit['email_critical_body']);
  }

    /**
   * Tests the cron email form configuration.
   */
  public function testCron() {
    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Get the settings form
    $this->drupalGet($this->settings_form_path);

    // Assure that we loaded the form.
    $session->statusCodeEquals(200);

    // Test that fieldset is there.
    $session->pageTextContains('Email background reports');

    // And that the critical emails are not enabled.
    $session->checkboxNotChecked('email_cron_enabled');

    // And that other email fields exists.
    $session->fieldExists('email_cron_to');
    $session->fieldExists('email_cron_subject');
    $session->fieldExists('email_cron_body');

    // Update the settings.
    $edit = [
      'email_cron_enabled' => 1,
      'email_cron_to' => 'ops@example.com',
      'email_cron_subject' => 'Test cron!',
      'email_cron_body' => 'Stuff happened.',
    ];
    $this->drupalPostForm($this->settings_form_path, $edit, 'Save configuration');

    // Reload the page.
    $this->drupalGet($this->settings_form_path);

    // Check the settings for change.
    $session->checkboxChecked('email_cron_enabled');
    $session->fieldValueEquals('email_cron_to', $edit['email_cron_to']);
    $session->fieldValueEquals('email_cron_subject', $edit['email_cron_subject']);
    $session->fieldValueEquals('email_cron_body', $edit['email_cron_body']);
  }

}
