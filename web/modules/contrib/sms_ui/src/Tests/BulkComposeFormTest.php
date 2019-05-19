<?php

namespace Drupal\sms_ui\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\sms\Tests\SmsFrameworkTestTrait;

/**
 * Tests the bulk compose user interfaces of SMS_UI.
 *
 * @group SMS UI
 */
class BulkComposeFormTest extends WebTestBase {

  use SmsFrameworkTestTrait;

  public static $modules = ['sms', 'sms_test_gateway', 'sms_ui'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->config('system.date')->set('country.default', 'NG')->save();
  }

  /**
   * Tests bulk sending without errors.
   */
  public function testBulkSend() {
    $gateway = $this->createMemoryGateway();
    $this->config('sms.settings')->set('fallback_gateway', $gateway->id())->save();
    $user = $this->drupalCreateUser(['access bulk sms ui', 'send sms', 'access sms ui']);
    $this->drupalLogin($user);
    $edit = [
      'sender' => trim($this->randomString()),
      'recipients' => '2342342342345, 4564564564567, 7897897897890, 09012345678',
      'message' => $this->randomGenerator->sentences(10),
      'send_direct' => TRUE,
    ];
    $url = new Url('sms_ui.send_bulk');
    $this->drupalPostForm($url, $edit, 'Send');
    // Assert the SMS success message.
    $this->assertText('The message was successfully sent to the following 4 recipients');
    $this->assertText('2342342342345');
    $this->assertText('4564564564567');
    $this->assertText('7897897897890');
    $this->assertText('2349012345678');
    $sms_message = $this->getTestMessages($gateway)[0];
    $this->assertUrl(new Url('sms_ui.send_status', ['uuid' => $sms_message->getUuid()], [
      'query' => ['destination' => $url->setAbsolute(FALSE)->toString()]]));
    $this->assertEqual($edit['message'], $sms_message->getMessage());

  }

  /**
   * Tests bulk sending with errors.
   */
  public function testBulkSendErrors() {
    $this->drupalGet(new Url('sms_ui.send_bulk'));
    // There should be an access denied error.
    $this->assertResponse(403);

    $gateway = $this->createMemoryGateway();
    $this->config('sms.settings')->set('fallback_gateway', $gateway->id())->save();
    $user = $this->drupalCreateUser(['access bulk sms ui']);
    $this->drupalLogin($user);
    $edit = [
      'sender' => trim($this->randomString()),
      'recipients' => '2342342342345, 4564564564567, 7897897897890',
      'message' => $this->randomGenerator->sentences(10),
    ];
    $url = new Url('sms_ui.send_bulk');
    $this->drupalPostForm($url, $edit, 'Send');
    // Expect an error that one is not allowed to send SMS.
    $this->assertText('You are not permitted to send SMS messages. Please contact the administrator.');

    // Grant role to send SMS.
    $user1 = $this->drupalCreateUser(['access bulk sms ui', 'send sms', 'access sms ui']);
    $this->drupalLogin($user1);

    // Confirm there is validation on posting incomplete content.
    $this->drupalPostForm($url, [], 'Send');
    $this->assertText('Sender field is required.');
    $this->assertText('Recipients field is required.');
    $this->assertText('Message field is required.');

    // Remove the default gateway setting.
    $this->config('sms.settings')->set('fallback_gateway', '')->save();
    $this->drupalPostForm($url, $edit, 'Send');
    // This should return an error since no gateways are configured.
    $this->assertText('There was an error in the SMS gateway configuration. Please contact the administrator.');
  }

  /**
   * Tests sender ID filtering / blocking.
   */
  public function testSenderIdBlock() {
    $gateway = $this->createMemoryGateway();
    $this->config('sms.settings')->set('fallback_gateway', $gateway->id())->save();

    // Grant role to send SMS.
    $user = $this->drupalCreateUser(['administer sms ui', 'access bulk sms ui', 'send sms', 'access sms ui']);
    $this->drupalLogin($user);

    $edit = [
      'sender_id_filter[excluded]' => 'jack, jill, hill',
      'sender_id_filter[included]' => $user->getAccountName() . ': jack',
    ];
    $this->drupalPostForm(new Url('sms_ui.admin_form'), $edit, 'Save configuration');

    // Confirm there is validation on posting filtered sender ID.
    $message = [
      'sender' => 'jill',
      'recipients' => '2342342342345',
      'message' => $this->randomGenerator->sentences(10),
      'send_direct' => TRUE,
    ];
    $url = new Url('sms_ui.send_bulk');
    $this->drupalPostForm($url, $message, 'Send');
    $this->assertText('The sender ID jill is not allowed. If you are the genuine owner of the sender ID, you can request access by mailing');

    // Confirm there is validation on posting filtered sender ID.
    $message['sender'] = 'jack';
    $this->drupalPostForm($url, $message, 'Send');

    // Assert the SMS success message.
    $this->assertText('The message was successfully sent to the following 1 recipient');
    $this->assertText('2342342342345');
    $sms_message = $this->getTestMessages($gateway)[0];
    $this->assertUrl(new Url('sms_ui.send_status', ['uuid' => $sms_message->getUuid()], [
      'query' => ['destination' => $url->setAbsolute(FALSE)->toString()]]));
    $this->assertEqual($message['message'], $sms_message->getMessage());
  }

}
