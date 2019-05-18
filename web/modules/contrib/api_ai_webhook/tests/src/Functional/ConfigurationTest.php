<?php

namespace Drupal\Tests\api_ai_webhook\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test config form.
 *
 * @group api_ai_webhook
 */
class ConfigurationTest extends BrowserTestBase {

  protected static $modules = ['api_ai_webhook'];

  /**
   * Tests 'headers' auth type settings.
   */
  public function testHeadersAuthType() {

    // Create a user with the right permission, and login.
    $account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($account);

    // Open the config page.
    $this->drupalGet('/admin/config/service/api_ai_webhook');

    // Prepare form values.
    $edit = [
      'type' => 'headers',
      'http_headers' => 'Lorem: ABC' . PHP_EOL . 'Ipsum: DEF',
    ];
    // Send the form.
    $this->drupalPostForm(NULL, $edit, 'op');
    // Verify the saved message.
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));

    // Get the config factory service.
    $config_factory = $this->container->get('config.factory');

    // Get variables.
    $auth_type = $config_factory->get('api_ai_webhook.settings')->get('auth.type');
    $auth_values = $config_factory->get('api_ai_webhook.settings')->get('auth.values');

    // Verify the config values are stored.
    $this->assertEquals('headers', $auth_type);
    $this->assertEquals(['Lorem', 'Ipsum'], $auth_values);

    // Verify the config data is in the State.
    $state_data = $this->container->get('state')->get('api_ai_webhook.auth');
    $this->assertEquals(['headers' => ['Lorem' => ' ABC', 'Ipsum' => ' DEF']], $state_data);
  }

}
