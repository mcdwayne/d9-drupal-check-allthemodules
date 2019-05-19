<?php

namespace Drupal\sms_ui\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessageInterface;
use Drupal\sms\Tests\SmsFrameworkTestTrait;
use Drupal\sms_ui\Entity\SmsHistory;

/**
 * Tests the User interface for SMS History.
 *
 * @group SMS UI
 */
class SmsHistoryUiTest extends WebTestBase {

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
   * Test that drafts are saved and edited properly.
   */
  public function testSaveDraft() {
    // Grant role to send SMS and view history.
    $user = $this->drupalCreateUser([
      'access bulk sms ui',
      'send sms',
      'access sms ui',
      'access own sms history']);
    $this->drupalLogin($user);
    $this->assertEqual([], SmsHistory::loadMultiple());

    $message = [
      'sender' => 'jack',
      'recipients' => '234234234234',
      'message' => $this->randomGenerator->sentences(10),
    ];
    $url = new Url('sms_ui.send_bulk');
    $this->drupalPostForm($url, $message, 'Save');
    $histories = array_values(SmsHistory::loadMultiple());
    $this->assertText('Your message was saved to draft.');
    $this->assertEqual(1, count($histories));
    $this->assertEqual($message['sender'], $histories[0]->getSender());
    $this->assertEqual(explode(',', $message['recipients']), $histories[0]->getRecipients());
    $this->assertEqual($message['message'], $histories[0]->getMessage());

    // Open the draft and save again. Ensure no duplicates are created.
    $message['sender'] = $this->randomMachineName();
    $message['recipients'] = '123123123123,345345345345,456456456456,567567567567';
    $this->drupalPostForm(new Url('sms_ui.send_bulk', [], ['query' => ['_stored' => $histories[0]->id()]]), $message, 'Save');
    $this->resetStorage();
    $histories1 = array_values(SmsHistory::loadMultiple());
    $this->assertEqual(1, count($histories1));
    $this->assertEqual($message['sender'], $histories1[0]->getSender());
    $this->assertEqual(explode(',', $message['recipients']), $histories1[0]->getRecipients());
    $this->assertNotEqual($message['sender'], $histories[0]->getSender());
    $this->assertNotEqual(explode(',', $message['recipients']), $histories[0]->getRecipients());
    $this->assertEqual($histories[0]->id(), $histories1[0]->id());

    // Save another draft and confirm that it is created.
    $message['recipients'] = '123123123123';
    $this->drupalPostForm(new Url('sms_ui.send_bulk'), $message, 'Save');
    $this->resetStorage();
    $histories2 = array_values(SmsHistory::loadMultiple());
    $this->assertEqual(2, count($histories2));
    $this->assertEqual($message['sender'], $histories2[1]->getSender());

    // Verify on listing page that two messages are in the drafts list.
    $this->drupalGet(new Url('sms_ui.history_draft'));
    $this->assertText('123123123123');
    $this->assertText('123123123123, 345345345345, ...2 more');
  }

  /**
   * Tests messages saved and sent from drafts.
   */
  public function testSaveSent() {
    // Grant role to send SMS and view history.
    $user = $this->drupalCreateUser([
      'access bulk sms ui',
      'send sms',
      'access sms ui',
      'access own sms history']);
    $this->drupalLogin($user);
    $this->assertEqual([], SmsHistory::loadMultiple());
    $message = [
      'sender' => $this->randomMachineName(),
      'recipients' => '234234234234,786786876876,231231231231,324324324324',
      'message' => $this->randomGenerator->sentences(10),
    ];
    $url = new Url('sms_ui.send_bulk');
    $this->drupalPostForm($url, $message, 'Save');
    $histories = array_values(SmsHistory::loadMultiple());
    $this->assertEqual(1, count($histories));

    // Open the draft and send. Verify that a saved sent message exists and the
    // draft is deleted.
    $gateway = $this->createMemoryGateway();
    $gateway->setSkipQueue(FALSE)->save();
    $this->config('sms.settings')->set('fallback_gateway', $gateway->id())->save();

    $this->drupalPostForm(new Url('sms_ui.send_bulk', [], ['query' => ['_stored' => $histories[0]->id()]]), ['send_direct' => TRUE], 'Send');
    $this->assertText('The message was successfully sent to the following 4 recipients');
    $this->assertText('234234234234');
    $this->assertText('786786876876');
    $this->assertText('231231231231');
    $this->assertText('324324324324');
    $this->resetStorage();
    $histories1 = array_values(SmsHistory::loadMultiple());
    $this->assertEqual(1, count($histories1));
    $this->assertEqual($histories[0]->id(), $histories1[0]->id());
    $this->assertEqual('sent', $histories1[0]->getStatus());

    $sms_message = $this->getTestMessages($gateway)[0];
    $this->assertEqual($sms_message->getMessage(), $histories1[0]->getMessage());

    // Clone the last sent message into a new one, send and verify.
    $this->drupalPostForm(new Url('sms_ui.send_bulk', [], ['query' => ['_stored' => $histories1[0]->id()]]), ['recipients' => '654654654654', 'send_direct' => TRUE], 'Send');
    $this->assertText('The message was successfully sent to the following 1 recipient');
    $this->assertText('654654654654');
    $this->resetStorage();
    $histories2 = array_values(SmsHistory::loadMultiple());
    $this->assertEqual(2, count($histories2));
    $this->assertEqual($histories1[0]->id(), $histories2[0]->id());
    $this->assertNotEqual($histories1[0]->id(), $histories2[1]->id());
    $this->assertEqual('sent', $histories2[1]->getStatus());

    // Verify on listing page that message was in the sent list.
    $this->drupalGet(new Url('sms_ui.history_sent'));
    $this->assertText('234234234234, 786786876876, ...2 more');
    $this->assertText('654654654654');
    $this->assertText('4 pending');
    $this->assertText('1 pending');
  }

  /**
   * Tests that queued messages are saved to history.
   */
  public function testSaveQueued() {
    // Grant role to send SMS and view history.
    $user = $this->drupalCreateUser([
      'access bulk sms ui',
      'send sms',
      'access sms ui',
      'create sms history',
      'access own sms history']);
    $this->drupalLogin($user);
    $this->assertEqual([], SmsHistory::loadMultiple());
    $message = [
      'sender' => $this->randomMachineName(),
      'recipients' => '987987987987,765765765765,543543543543,432432432432',
      'message' => $this->randomGenerator->sentences(10),
    ];
    // Send to the queue and verify it is visible in the queue display.
    $gateway = $this->createMemoryGateway();
    $gateway
      ->setSkipQueue(FALSE)
      ->setRetentionDuration(Direction::OUTGOING, -1)
      ->save();
    $this->config('sms.settings')->set('fallback_gateway', $gateway->id())->save();
    $this->drupalPostForm(new Url('sms_ui.send_bulk'), $message, 'Send');
    $histories1 = array_values(SmsHistory::loadMultiple());
    $this->assertEqual(1, count($histories1));
    $this->assertEqual('queued', $histories1[0]->getStatus());

    // Verify on listing page that message was in the sent list.
    $this->drupalGet(new Url('sms_ui.history_queued'));
    $this->assertText('987987987987, 765765765765, ...2 more');
    $this->assertText('queued');

    $this->drupalGet(new Url('system.cron', ['key' => \Drupal::state()->get('system.cron_key')]));
    $this->resetStorage();
    $histories2 = array_values(SmsHistory::loadMultiple());
    $this->assertEqual(1, count($histories2));
    $this->assertEqual('sent', $histories2[0]->getStatus());
    $this->assertTrue($histories2[0]->getSmsMessages()[0] instanceof SmsMessageInterface);

    // Verify on listing page that message is in the sent list.
    $this->drupalGet(new Url('sms_ui.history_sent'));
    $this->assertText('987987987987, 765765765765, ...2 more');
    $this->assertText('4 pending');

    // Verify on listing page that message is no more in the queued list.
    $this->drupalGet(new Url('sms_ui.history_queued'));
    $this->assertNoText('987987987987, 765765765765, ...2 more');
  }

  /**
   * Tests that users only have access to view their own history.
   */
  public function testHistoryViewAccess() {
    // Grant role to send SMS and view history.
    $user1 = $this->drupalCreateUser([
      'access bulk sms ui',
      'send sms',
      'access sms ui',
      'access own sms history']);
    $this->drupalLogin($user1);
    $message1['sent'] = [
      'sender' => $this->randomMachineName(),
      'recipients' => '987987987987,765765765765,543543543543,432432432432',
      'message' => $this->randomGenerator->sentences(10),
    ];
    $message1['draft'] = [
      'sender' => $this->randomMachineName(),
      'recipients' => '21847862768',
      'message' => $this->randomGenerator->sentences(10),
    ];
    // Send and save messages.
    $gateway = $this->createMemoryGateway();
    $gateway->setSkipQueue(TRUE)->save();
    $this->config('sms.settings')->set('fallback_gateway', $gateway->id())->save();
    $this->drupalPostForm(new Url('sms_ui.send_bulk'), $message1['sent'], 'Send');
    $this->drupalPostForm(new Url('sms_ui.send_bulk'), $message1['draft'], 'Save');

    // Grant role to send SMS and view history.
    $user2 = $this->drupalCreateUser([
      'access bulk sms ui',
      'send sms',
      'access sms ui',
      'access own sms history']);
    $this->drupalLogin($user2);
    $message2['sent'] = [
      'sender' => $this->randomMachineName(),
      'recipients' => '149876543120',
      'message' => $this->randomGenerator->sentences(10),
    ];
    $message2['draft'] = [
      'sender' => $this->randomMachineName(),
      'recipients' => '192749232247,765762575723',
      'message' => $this->randomGenerator->sentences(10),
    ];
    // Send and save messages.
    $gateway = $this->createMemoryGateway();
    $gateway->setSkipQueue(TRUE)->save();
    $this->config('sms.settings')->set('fallback_gateway', $gateway->id())->save();
    $this->drupalPostForm(new Url('sms_ui.send_bulk'), $message2['sent'], 'Send');
    $this->drupalPostForm(new Url('sms_ui.send_bulk'), $message2['draft'], 'Save');

    // Verify on listing page that user2 cannot see user1's history.
    $this->drupalGet(new Url('sms_ui.history_sent'));
    $this->assertText($message2['sent']['message']);
    $this->assertNoText($message2['draft']['message']);
    $this->assertNoText($message1['sent']['message']);
    $this->assertNoText($message1['draft']['message']);

    $this->drupalGet(new Url('sms_ui.history_draft'));
    $this->assertText($message2['draft']['message']);
    $this->assertNoText($message2['sent']['message']);
    $this->assertNoText($message1['sent']['message']);
    $this->assertNoText($message1['draft']['message']);

    $this->drupalLogin($user1);
    // Verify on listing page that user1 cannot see user2's history.
    $this->drupalGet(new Url('sms_ui.history_sent'));
    $this->assertText($message1['sent']['message']);
    $this->assertNoText($message1['draft']['message']);
    $this->assertNoText($message2['sent']['message']);
    $this->assertNoText($message2['draft']['message']);

    $this->drupalGet(new Url('sms_ui.history_draft'));
    $this->assertText($message1['draft']['message']);
    $this->assertNoText($message1['sent']['message']);
    $this->assertNoText($message2['sent']['message']);
    $this->assertNoText($message2['draft']['message']);
  }

  public function _testDeleteHistory() {
    $this->pass('Not yet implemented');
  }

  /**
   * Helper to reset the storage.
   */
  protected function resetStorage() {
    $manager = $this->container->get('entity_type.manager');
    $manager->getStorage('sms_history')->resetCache();
    $manager->getStorage('sms')->resetCache();
  }

}
