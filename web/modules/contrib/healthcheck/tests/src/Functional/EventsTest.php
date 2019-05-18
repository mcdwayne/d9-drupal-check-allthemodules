<?php

namespace Drupal\Tests\healthcheck\Functional;

use Drupal\Core\Url;
use Drupal\healthcheck\Form\HealthcheckSettingsForm;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the events system for Healthcheck.
 *
 * @group healthcheck
 */
class EventsTest extends BrowserTestBase {

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

    // Provides a finding for each status.
    'healthcheck_findings_test',

    // Provides a custom EventSubscriber that sets state vars when run.
    'healthcheck_events_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Configure Healthcheck to:
    //   * Only run the testing category.
    //   * Run ever cron run.
    \Drupal::configFactory()
      ->getEditable(HealthcheckSettingsForm::CONF_ID)
      ->set('categories', ['testing'])
      ->set('run_every', '1')
      ->save();
  }

  public function testCritical() {
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

    // Check the state.
    $this->assertTrue($this->container->get('state')->get('healthcheck_events_test.doCritical', FALSE));
    $this->assertTrue($this->container->get('state')->get('healthcheck_events_test.doRun', FALSE));
  }

  public function testRun() {
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

    // Check the state.
    $this->assertTrue($this->container->get('state')->get('healthcheck_events_test.doRun', FALSE));
  }

  public function testCron() {
    // Run cron.
    \Drupal::service('cron')->run();

    // Check the state.
    $this->assertTrue($this->container->get('state')->get('healthcheck_events_test.doCron', FALSE));
  }
}
