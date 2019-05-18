<?php

namespace Drupal\Tests\config_override_message\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Config override message browser test.
 *
 * @group config_override_message
 */
class ConfigOverrideMessageFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['config_override_message', 'config_override_message_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set $config_directories['override'] for site wide config overrides.
    $override_path = drupal_get_path('module', 'config_override_message') . '/tests/config/override';
    $settings_filename = $this->siteDirectory . '/settings.php';
    chmod($settings_filename, 0777);
    $settings_php = file_get_contents($settings_filename);
    $settings_php .= "\n\$config_directories['override'] = '$override_path';\n";
    file_put_contents($settings_filename, $settings_php);
  }

  /**
   * Test config override message.
   */
  public function testConfigOverrideMessage() {
    $account_admin = $this->createUser([
      'administer site configuration',
    ]);

    $account_view = $this->createUser([
      'administer site configuration',
      'view config override message',
    ]);

    /**************************************************************************/

    // Check messages are not displayed with 'view config override message'
    // permission.
    $this->drupalLogin($account_admin);
    $this->drupalGet('/admin/config/system/site-information');
    $this->assertSession()->responseNotContains('This is a test of site wide config override messages.');
    $this->assertSession()->responseNotContains('This is a test of module config override messages.');

    // Check messages area displayed with 'view config override message'
    // permission.
    $this->drupalLogin($account_view);
    $this->drupalGet('/admin/config/system/site-information');
    $this->assertSession()->responseContains('This is a test of site wide config override messages.');
    $this->assertSession()->responseContains('This is a test of module config override messages.');
  }

}
