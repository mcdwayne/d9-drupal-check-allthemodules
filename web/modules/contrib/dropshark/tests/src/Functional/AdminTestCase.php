<?php

namespace Drupal\Tests\dropshark\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class DropSharkAdminTestCase.
 *
 * @group dropshark
 */
class AdminTestCase extends BrowserTestBase {

  /**
   * Administrative user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['dropshark', 'dropshark_testing'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests the configuration screen found at admin/config/services/dropshark.
   */
  public function testConfigScreen() {
    $initialHelpText = "In order to register your site with the DropShark service, you'll need to enter your credentials and site identifier.";
    $configuredHelpText = 'Your site is registered and will send data to DropShark. Log in to DropShark to analyze your data.';

    // Initial configuration.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/services/dropshark');
    $this->assertSession()->pageTextContains($initialHelpText);

    // Submit bad configs.
    $edit['email'] = 'test@user.com';
    $edit['password'] = 'pwd';
    $edit['site_id'] = '0bb250ce-ffd5-4563-81fa-c8a165cd6af8';
    $this->submitForm($edit, t('Register site'));
    $this->assertSession()->pageTextContains('Unable to register your site');
    $this->assertSession()->pageTextNotContains('Your site has been registered with DropShark');

    // Submit valid configs.
    $edit['password'] = 'password';
    $this->submitForm($edit, 'Register site');
    $this->assertSession()->pageTextContains('Your site has been registered with DropShark');
    $this->assertSession()->pageTextNotContains('Unable to register your site');
    $this->assertSession()->pageTextNotContains($initialHelpText);
    $this->assertSession()->pageTextContains($configuredHelpText);
    $this->assertSession()->pageTextContains('0bb250ce-ffd5-4563-81fa-c8a165cd6af8');

    // Perform test connection.
    $edit = [];
    $this->submitForm($edit, 'Check');
    $this->assertSession()->pageTextContains('Connection successfully verified');
    $this->assertSession()->pageTextNotContains('Unable to verify the site connection');

    // Use a garbage token, test connection (expected to fail).
    \Drupal::state()->set('dropshark.site_token', 'invalid_token');
    $this->submitForm($edit, 'Check');
    $this->assertSession()->pageTextContains('Unable to verify the site connection');
    $this->assertSession()->pageTextNotContains('Connection successfully verified');

    // Perform reset.
    $this->submitForm($edit, 'Reset');
    $this->assertSession()->pageTextContains($initialHelpText);
  }

  /**
   * Tests requirements set on status report page.
   */
  public function testRequirements() {
    $regText = 'Information collected from your site cannot be submitted to DropShark. Please register your site to utilize DropShark.';
    $connText = 'Information collected from your site cannot be submitted to DropShark. Please check your DropShark configuration.';
    $okText = 'Your site is successfully communicating with DropShark.';

    // Un-registered.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains($regText);
    $this->assertSession()->pageTextNotContains($connText);
    $this->assertSession()->pageTextNotContains($okText);

    // Registered, but unable to connect.
    \Drupal::state()->set('dropshark.site_token', 'invalid_token');
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains($regText);
    $this->assertSession()->pageTextContains($connText);
    $this->assertSession()->pageTextNotContains($okText);

    // Successful connection.
    \Drupal::state()->set('dropshark.site_token', 'valid_token');
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains($regText);
    $this->assertSession()->pageTextNotContains($connText);
    $this->assertSession()->pageTextContains($okText);
  }

}
