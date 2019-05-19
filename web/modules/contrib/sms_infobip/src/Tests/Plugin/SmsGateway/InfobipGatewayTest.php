<?php

namespace Drupal\sms_infobip\Tests\Plugin\SmsGateway;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsGateway;
use Drupal\sms\Message\SmsMessage;

/**
 * Web tests for infobip gateway plugin.
 *
 * @group SMS Gateways
 */
class InfobipGatewayTest extends WebTestBase {

	public static $modules = ['sms', 'sms_gateway_base', 'sms_infobip'];
	
  /**
   * Tests Infobip API directly.
   */
  public function testApi() {
  	// Set up gateway.
		/** @var \Drupal\sms\Entity\SmsGatewayInterface $gateway */
		$gateway = SmsGateway::create([
			'id' => $this->randomMachineName(),
			'label' => $this->randomString(),
			'plugin' => 'infobip',
			'settings' => [
				'ssl' => FALSE,
				'username' => 'test_user',
				'password' => 'password',
				'server' => 'api.infobip.com',
				'port' => 80,
        // @todo Need tests with delivery reports on.
				'reports' => FALSE,
			],
		]);

		// Test gateway and ensure we actually have an answer. Expecting an
    // authentication failure since the username / password don't exist.
    $sms_message = new SmsMessage($this->randomMachineName(), ['234234234234'], 'test message');
    $response = $gateway->getPlugin()->send($sms_message);

    // Expect the request to fail because of authentication failure.
    $this->assertNotNull($response->getError());
    $this->assertEqual(substr($response->getErrorMessage(), 0, 43), 'HTTP response exception (401) Client error:');

    // @todo More tests with valid credentials.
    // Test on credits command
//    $credits = $gateway->credits();
//    $this->assertTrue($credits, t('Execute "credits" command. Credits: @credits', array('@credits' => $credits)), $group);
//
//    // Send text using SPLIT GET method
//    $gateway->config(array('method' => INFOBIP_HTTP_GET_SPLIT));
//    $response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
//    $this->assertTrue($response['status'], t('Send SMS using SPLIT GET'));
//
//    // Send text using NORMAL GET method
//    $gateway->config(array('method' => INFOBIP_HTTP_GET));
//    $response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
//    $this->assertTrue($response['status'], t('Send SMS using NORMAL GET'));
//
//    // Send text using XML POST method
//    $gateway->config(array('method' => INFOBIP_HTTP_POST));
//    $response = $gateway->send('2348134496448', $this->randomMessage(), array('sender' => $this->randomSender(20)), $group);
//    $this->assertTrue($response['status'], t('Send SMS using XML POST'));
//
//    // Tests not yet implemented
//    $response = $gateway->delivery_pull('032101822485962236');
//    debug($response, 'DLR Response');
//    $this->assertTrue(count($response), t('DeliveryReports received'), $group);
  }

  /**
   * Tests creating a gateway using the default configuration form.
   */
  public function testConfigurationForm() {
    $this->drupalLogin($this->rootUser);
    $edit = [
      'label' => $this->randomString(),
      'status' => 1,
      'plugin_id' => 'infobip',
      'id' => strtolower($this->randomMachineName()),
    ];
    $this->drupalPostForm(new Url('entity.sms_gateway.add'), $edit, 'Save');
    $this->assertResponse(200);
    $this->assertUrl(new Url('entity.sms_gateway.edit_form', ['sms_gateway' => $edit['id']]));
    // Assert default value of port field.
    $this->assertFieldByName('settings[port]', 80);

    $settings = [
      'settings[ssl]' => FALSE,
      'settings[server]' => 'api.infobip.com',
      'settings[port]' => '',
      'settings[username]' => 'test_user',
      'settings[password]' => 'password',
      'settings[reports]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $settings, 'Save');
    $this->assertResponse(200);
    $this->assertText('HTTP response exception (401) Client error:');
  }

}
