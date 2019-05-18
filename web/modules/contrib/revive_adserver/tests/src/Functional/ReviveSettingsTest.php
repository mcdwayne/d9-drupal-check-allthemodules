<?php

namespace Drupal\Tests\revive_adserver\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\revive_adserver\Traits\ReviveTestTrait;

/**
 * Tests the revive adserver configuration.
 *
 * @group revive_adserver
 */
class ReviveSettingsTest extends BrowserTestBase {

  use ReviveTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'revive_adserver',
  ];

  /**
   * A user with permissions to access the revive settings page.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log in as a user, that can configure the revive adserver settings.
    $this->user = $this->drupalCreateUser([
      'administer revive_adserver',
      'administer site configuration',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the requirement warnings on module configuration.
   */
  public function testRequirementsWarnings() {
    // Test, that the requirement warning appears, when module is not
    // configured.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()
      ->pageTextContains('Revive Adserver is not properly configured.');
    $this->assertSession()
      ->linkByHrefExists('admin/config/services/revive-adserver');

    // Test, that the requirement warning is not shown, when the module is
    // configured properly.
    $this->configureModule();
    $this->setupAdZones();
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains('Revive Adserver');
  }

}
