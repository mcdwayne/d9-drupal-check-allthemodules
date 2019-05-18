<?php

namespace Drupal\Tests\healthcheck\Functional;

use Drupal\Core\Url;
use Drupal\healthcheck\Form\HealthcheckSettingsForm;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the adhoc report screen.
 *
 * @group healthcheck
 */
class AdhocTest extends BrowserTestBase {

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
    'healthcheck_findings_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set the Healthcheck to only check the 'testing' category.
    \Drupal::configFactory()
      ->getEditable(HealthcheckSettingsForm::CONF_ID)
      ->set('categories', ['testing'])
      ->save();
  }

  public function testAdhocPageAccess() {
    // Create the user and login.
    $account = $this->drupalCreateUser(['access content',
      'run healthcheck'
    ]);

    // Start the session.
    $session = $this->assertSession();

    // Login using the account we've created.
    $this->drupalLogin($account);

    // Navigate to the adhoc report page.
    $this->drupalGet(Url::fromRoute('healthcheck.report_controller_runReport'));

    // Check the page.
    $session->statusCodeEquals(200);
    $session->pageTextContains('Finding status Critical');
    $session->pageTextContains('Finding status Action Requested');
    $session->pageTextContains('Finding status Needs Review');
    $session->pageTextContains('Finding status No Action Required');
    $session->pageTextContains('Finding status Not Performed');
  }

}
