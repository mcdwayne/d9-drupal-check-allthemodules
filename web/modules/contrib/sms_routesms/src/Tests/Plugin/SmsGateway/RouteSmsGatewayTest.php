<?php

namespace Drupal\sms_routesms\Tests\Plugin\SmsGateway;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Message\SmsMessage;

/**
 * Web tests for RouteSMS gateway plugin.
 *
 * @group SMS Gateways
 */
class RouteSmsGatewayTest extends WebTestBase {

	public static $modules = ['sms', 'sms_gateway_base', 'sms_routesms'];
	
  /**
   * Tests RouteSMS API directly.
   */
  public function testApi() {
  	// Set up gateway.
		/** @var \Drupal\sms\Entity\SmsGatewayInterface $gateway */
		$gateway = SmsGateway::create([
			'id' => $this->randomMachineName(),
			'label' => $this->randomString(),
			'plugin' => 'routesms',
			'settings' => [
				'ssl' => FALSE,
				'username' => 'test_user',
				'password' => 'password',
				'server' => 'smsplus.routesms.com',
				'port' => 80,
        // @todo Need tests with delivery reports on.
				'reports' => FALSE,
			],
		]);

		// Test gateway and ensure we actually have an answer.
    $sms_message = new SmsMessage($this->randomMachineName(), ['234234234234', '1234567890'], 'test message');
		$response = $gateway->getPlugin()->send($sms_message);

		// Expect the request to fail because of authentication failure.
    $this->assertNotNull($response->getError());
    $this->assertEqual($response->getErrorMessage(), 'Invalid value in username or password field');

    // @todo More tests with valid credentials.
  }

	/**
	 * Tests creating a gateway using the default configuration form.
	 */
	public function testConfigurationForm() {
		$this->drupalLogin($this->rootUser);
		$edit = [
			'label' => $this->randomString(),
			'status' => 1,
			'plugin_id' => 'routesms',
			'id' => strtolower($this->randomMachineName()),
		];
		$this->drupalPostForm(new Url('entity.sms_gateway.add'), $edit, 'Save');
		$this->assertResponse(200);
		$this->assertUrl(new Url('entity.sms_gateway.edit_form', ['sms_gateway' => $edit['id']]));
		// Assert default value of port field.
		$this->assertFieldByName('settings[port]', 80);

		$settings = [
			'settings[ssl]' => FALSE,
			'settings[server]' => 'smsplus.routesms.com',
			'settings[port]' => '8080',
			'settings[username]' => 'test_user',
			'settings[password]' => 'password',
			'settings[reports]' => FALSE,
			'settings[test_number]' => '2234234234',
		];
		$this->drupalPostForm(NULL, $settings, 'Save');
		$this->assertResponse(200);
		$this->assertText('Invalid value in username or password field.');
	}

}
