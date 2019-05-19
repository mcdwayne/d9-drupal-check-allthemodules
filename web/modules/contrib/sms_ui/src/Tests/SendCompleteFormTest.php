<?php

namespace Drupal\sms_ui\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\sms\Tests\SmsFrameworkTestTrait;

/**
 * Tests the user SendCompleteForm.
 *
 * @group sms_ui
 */
class SendCompleteFormTest extends WebTestBase {

  use SmsFrameworkTestTrait;

  protected static $modules = ['sms_ui', 'sms', 'sms_test_gateway'];

  public function setUp() {
    parent::setUp();
  }

  public function testSendCompleteForm() {
    $gateway = $this->createMemoryGateway();
    $this->config('sms.settings')->set('fallback_gateway', $gateway->id())->save();
    $user = $this->drupalCreateUser(['send sms', 'access bulk sms ui', 'access sms ui']);
    $this->drupalLogin($user);
    $url = new Url('sms_ui.send_bulk');
    $this->drupalGet($url);
    $numbers = $this->randomPhoneNumbers();
    $edit = [
      'sender' => $this->randomMachineName(),
      'recipients' => implode("\n", $numbers),
      'message' => $this->randomGenerator->sentences(10),
      'send_direct' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Send');
    $sms_message = $this->getTestMessages($gateway)[0];
    $this->assertUrl(new Url('sms_ui.send_status', ['uuid' => $sms_message->getUuid()],
      ['query' => ['destination' => $url->setAbsolute(FALSE)->toString()]]));
    $this->assertText(trim($numbers[0], '+'));
    $this->assertText(trim($numbers[count($numbers) - 1], '+'));
    $this->assertText('The message was successfully sent to the following ' . count($numbers) . ' recipients');
  }

}
