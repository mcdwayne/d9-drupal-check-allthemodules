<?php

namespace Drupal\sms_gateway_base\Tests\Plugin\SmsGateway;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the functionality provided by the DefaultGatewayPluginBase.
 *
 * @group SMS Gateways
 */
class DefaultGatewayPluginBaseTest extends WebTestBase {

	public static $modules = ['sms', 'sms_gateway_base', 'sms_gateway_test'];
	
  /**
   * Tests creating a gateway using the default configuration form.
   */
  public function testConfigurationForm() {
    $this->drupalLogin($this->rootUser);
    $edit = [
      'label' => $this->randomString(),
      'status' => 1,
      'plugin_id' => 'foo_llama',
      'id' => strtolower($this->randomMachineName()),
    ];
    $this->drupalPostForm(new Url('entity.sms_gateway.add'), $edit, 'Save');
    $this->assertResponse(200);
    $edit_url = new Url('entity.sms_gateway.edit_form', ['sms_gateway' => $edit['id']]);
    $this->assertUrl($edit_url);
    // Assert default value of port field.
    $this->assertFieldByName('settings[port]', 80);

    $settings = [
      'settings[ssl]' => FALSE,
      'settings[server]' => 'example.com',
      'settings[port]' => 80,
      'settings[username]' => $this->randomMachineName(),
      'settings[password]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $settings, 'Save');
    $this->assertResponse(200);
    $this->assertText('Gateway saved.');
    $this->assertText(htmlentities($edit['label']), "{$edit['label']} found");
    $this->assertUrl(new Url('sms.gateway.list'));

  	// Simulate an exception in the settings using the test gateway.
    $bad_settings = [
      'settings[ssl]' => FALSE,
      'settings[server]' => 'example.com',
      'settings[username]' => $this->randomMachineName(),
      'settings[password]' => $this->randomMachineName(),
      'settings[simulate_error][message]' => 'An error has been encountered...',
      'settings[simulate_error][code]' => 8430,
    ];
    $this->drupalPostForm($edit_url, $bad_settings, 'Save');
    $this->assertResponse(200);
    $this->assertText('HTTP response exception (8430) An error has been encountered...');
    $this->assertUrl($edit_url);

    // Simulate a validation error with invalid server provided.
    $worse_settings = [
      'settings[ssl]' => FALSE,
      // Use an invalid server name.
      'settings[server]' => '\\',
      'settings[port]' => '1',
      'settings[username]' => $this->randomMachineName(),
      'settings[password]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm($edit_url, $worse_settings, 'Save');
    $this->assertResponse(200);
    $this->assertText('Gateway API server was not provided');
    $this->assertUrl($edit_url);
  }

  /**
   * @todo
   */
  public function testInvalidFormSubmission() {

  }

  /**
   * @todo
   */
  public function testErrorMessages() {
    // @todo: Tests needed for the error messages.
  }

}
