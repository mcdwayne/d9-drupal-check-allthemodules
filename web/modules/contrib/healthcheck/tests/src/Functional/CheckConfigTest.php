<?php

namespace Drupal\Tests\healthcheck\Functional;

use Drupal\Core\Url;
use Drupal\healthcheck\Form\HealthcheckSettingsForm;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the CheckConfig system and the check config form..
 *
 * @group healthcheck
 */
class CheckConfigTest extends BrowserTestBase {

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
  protected $check_list_path;

  /**
   * Modules to enable for the test.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'system',
    'user',
    'healthcheck',
    'healthcheck_config_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Get the settings form path.
    $this->check_list_path = Url::fromRoute('entity.checkconfig.collection');

    // Set the Healthcheck to only check the 'testing' category.
    \Drupal::configFactory()
      ->getEditable(HealthcheckSettingsForm::CONF_ID)
      ->set('categories', ['testing'])
      ->save();

    // Create a healthcheck admin user.
    $this->account = $this->drupalCreateUser([
      'run healthcheck',
      'configure healthcheck',
    ]);
  }

  public function testConfigList() {
    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Get the check config form
    $this->drupalGet($this->check_list_path);

    // Assure that we loaded the page.
    $session->statusCodeEquals(200);

    $session->pageTextContains('Configurable Check test');
  }

  public function testConfigForm() {
    $check_form_path = '/admin/config/system/healthcheck/checks/config_test/edit';

    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Navigate to the adhoc report page.
    $this->drupalGet(Url::fromRoute('healthcheck.report_controller_runReport'));

    // Check the page that the default setting is used.
    $session->statusCodeEquals(200);
    $session->pageTextContains('Config check is finding_not_performed');

    // Get the check config form
    $this->drupalGet($check_form_path);

    // Assure that we loaded the form.
    $session->statusCodeEquals(200);

    // Check if our target setting exists.
    $session->optionExists('status', 'Action Requested');

    // Change the setting and post the form.
    $edit = [
      'status' => '15',
    ];
    $this->drupalPostForm($check_form_path, $edit, 'Save');

    // Go back to the check config form
    $this->drupalGet($check_form_path);

    /** @var NodeElement $status */
    $status = $session->fieldExists('status')->getValue();

    // Check that value has been updated.
    $this->assertTrue('15' == $status, 'Settings form for config_test did not update!');

    // Navigate to the adhoc report page once more.
    $this->drupalGet(Url::fromRoute('healthcheck.report_controller_runReport'));

    // Check the page that the updated setting is used.
    $session->statusCodeEquals(200);
    $session->pageTextContains('Config check is finding_action_requested');
  }
}
