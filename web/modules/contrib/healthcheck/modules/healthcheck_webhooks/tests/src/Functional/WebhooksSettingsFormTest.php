<?php


namespace Drupal\Tests\healthcheck_webhooks\Functional;


use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the settings form.
 *
 * @group healthcheck_webhooks
 */
class WebhooksSettingsFormTest extends BrowserTestBase {

  protected $account;

  protected $settings_form_path;

  public static $modules = [
    'user',
    'healthcheck',
    'healthcheck_webhooks',
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
    $session->pageTextContains('Webhooks integration');

    // Check that the field exists.
    $session->fieldExists('zapier');

    // Update the settings.
    $edit = [
      'zapier' => 'http://example.com',
    ];
    $this->drupalPostForm($this->settings_form_path, $edit, 'Save configuration');

    // Reload the page.
    $this->drupalGet($this->settings_form_path);

    // Check that the field now has the new value.
    $session->fieldValueEquals('zapier', $edit['zapier']);
  }
}
