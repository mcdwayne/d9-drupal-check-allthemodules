<?php

namespace Drupal\Tests\marketing_cloud\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\marketing_cloud\MarketingCloudSession;

/**
 * Tests the base marketing_cloud module.
 *
 * @group marketing_cloud
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MarketingCloudTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['marketing_cloud'];

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;
  protected $session;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->adminUser = $this->drupalCreateUser(['administer_marketing_cloud']);
    $this->drupalLogin($this->adminUser);
    $this->session = new MarketingCloudSession();
    $this->config('marketing_cloud.settings')
      ->set('client_id', 'testingid')
      ->set('client_secret', 'testingsecret')
      ->set('validate_json', TRUE)
      ->set('do_not_send', TRUE)
      ->save();
  }

  /**
   * Tests that config page and routing has been created.
   */
  public function testConfigPageExists() {
    $this->drupalGet('admin/config/marketing_cloud');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the fetch token.
   */
  public function testMarketingCloudSession() {
    // Expected start values.
    $this->assertTrue($this->config('marketing_cloud.settings')->get('do_not_send'));
    $this->assertEquals("0", $this->config('marketing_cloud.settings')->get('token'));
    $this->assertFalse($this->config('marketing_cloud.settings')->get('requesting_token'));

    // Reset to new defaults.
    $this->session->resetToken();
    $this->assertFalse($this->config('marketing_cloud.settings')->get('token'));
    $this->assertFalse($this->config('marketing_cloud.settings')->get('requesting_token'));
  }

  /**
   * Tests the config object.
   */
  public function testMarketingCloudConfig() {
    // Ensure update 8001 has applied.
    $this->assertNull($this->config('marketing_cloud.settings')->get('validate_schema'));
  }

}
