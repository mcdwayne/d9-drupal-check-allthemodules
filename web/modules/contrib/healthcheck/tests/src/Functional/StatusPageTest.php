<?php


namespace Drupal\Tests\healthcheck\Functional;

use Drupal\healthcheck\Form\HealthcheckSettingsForm;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Test the Drupal status page for Healthcheck integration
 *
 * @group healthcheck
 */
class StatusPageTest extends BrowserTestBase {

  /**
   * A user account with 'access site reports' permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The path to the status page.
   *
   * @var string
   */
  protected $status_page_path;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'healthcheck',
    'healthcheck_findings_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Get the status page path.
    $this->status_page_path = Url::fromRoute('system.status');

    // Create an admin user.
    $this->account = $this->drupalCreateUser([
      'administer site configuration',
    ]);

    // Configure Healthcheck to:
    //   * Only run the testing category.
    //   * Run every cron run.
    \Drupal::configFactory()
      ->getEditable(HealthcheckSettingsForm::CONF_ID)
      ->set('categories', ['testing'])
      ->set('run_every', '1')
      ->save();
  }

  /**
   * Test the integration with the admin > reports > status page.
   */
  public function testStatusPage() {
    // Start the session.
    $session = $this->assertSession();

    // Login as our account.
    $this->drupalLogin($this->account);

    // Get the status page.
    $this->drupalGet($this->status_page_path);

    // Assure we loaded the status page with proper permissions.
    $session->statusCodeEquals(200);

    // And that the page displays a "Healthcheck" item.
    $session->pageTextContains('Healthcheck');

    // And that the last run has never occurred.
    $session->pageTextContains('Last run: Never');

    // Run cron, forcing Healthcheck to run.
    \Drupal::service('cron')->run();

    // Get the status page again.
    $this->drupalGet($this->status_page_path);

    // Check the status page still loads.
    $session->statusCodeEquals(200);

    // And that now the "Healthcheck" item shows it was run.
    $session->pageTextNotContains('Last run: Never');
  }

}
