<?php

namespace Drupal\Tests\sms_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Tests\SmsFrameworkTestTrait;
use Drupal\sms_ui\Entity\SmsHistory;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\sms_ui\Entity\SmsHistory
 *
 * @group SMS History
 */
class SmsHistoryEntityTest extends KernelTestBase {

  use SmsFrameworkTestTrait;

  public static $modules = ['user', 'sms', 'telephone', 'dynamic_entity_reference', 'sms_ui', 'sms_test_gateway'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('sms');
    $this->installEntitySchema('sms_result');
    $this->installEntitySchema('sms_report');
    $this->installEntitySchema('sms_history');
  }

  /**
   * @covers ::getStatus
   * @covers ::setStatus
   * @covers ::setSmsMessages
   * @covers ::getSmsMessages
   * @covers ::getMessage
   * @covers ::getSender
   * @covers ::getResults
   * @covers ::getReports
   * @covers ::getOwner
   * @covers ::getExpiry
   * @covers ::setExpiry
   */
  public function testSetGetMessages() {
    $messages = [];
    $results = [];
    $reports = [];
    $message_text = $this->getRandomGenerator()->paragraphs();
    $sender = $this->randomMachineName();
    User::create(['uid' => 7, 'name' => 'user'])->save();
    for ($i = 0; $i < 3; $i++) {
      $message = SmsMessage::convertFromSmsMessage($this->randomSmsMessage(7))
        ->setMessage($message_text)
        ->setSender($sender);
      $result = $this->createMessageResult($message);
      $message->setResult($result);
      $messages[] = $message;
      $results[] = $result;
      $reports += $result->getReports();
    }
    $history = (SmsHistory::create())
      ->setSmsMessages($messages)
      ->setStatus('draft')
      ->setExpiry(REQUEST_TIME + 123456);

    $this->assertEquals($messages, $history->getSmsMessages(), 'Messages set and get correctly');
    $this->assertEquals($results, $history->getResults(), 'Message Results were aggregated correctly.');
    $this->assertEquals($reports, $history->getReports(), 'Delivery Reports were aggregated correctly.');
    $this->assertEquals($message_text, $history->getMessage(), 'Message text is correct');
    $this->assertEquals($sender, $history->getSender(), 'Sender is correct');
    $this->assertEquals('draft', $history->getStatus(), 'Status is correct');
    $this->assertEquals(User::load(7), $history->getOwner(), 'Owner is correct');
    $this->assertEquals(REQUEST_TIME + 123456, $history->getExpiry(), 'Owner is correct');
  }

  public function testGetHistoryForMessage() {
    $sms_message = SmsMessage::convertFromSmsMessage($this->randomSmsMessage(null));
    $sms_message->save();
    $this->assertNull(SmsHistory::getHistoryForMessage($sms_message));

    $sms_messages1 = [
      SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL)),
      SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL)),
      SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL)),
    ];
    $history1 = (SmsHistory::create())
      ->setSmsMessages($sms_messages1)
      ->setStatus('queued');
    $history1->save();

    $sms_messages2 = [
      SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL)),
      SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL)),
      SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL)),
    ];
    $history2 = (SmsHistory::create())
      ->setSmsMessages($sms_messages2)
      ->setStatus('sent');
    $history2->save();

    $this->assertSame($history1->id(), SmsHistory::getHistoryForMessage($sms_messages1[0])->id());
    $this->assertSame($history1->id(), SmsHistory::getHistoryForMessage($sms_messages1[1])->id());
    $this->assertSame($history1->id(), SmsHistory::getHistoryForMessage($sms_messages1[2])->id());
    $this->assertSame($history2->id(), SmsHistory::getHistoryForMessage($sms_messages2[0])->id());
    $this->assertSame($history2->id(), SmsHistory::getHistoryForMessage($sms_messages2[1])->id());
    $this->assertSame($history2->id(), SmsHistory::getHistoryForMessage($sms_messages2[2])->id());
  }

  public function testSetGetMessagesWithBadData() {
    $history = SmsHistory::create();
    $this->assertEquals([], $history->getSmsMessages());
    $this->assertEquals([], $history->getResults());
    $this->assertEquals([], $history->getReports());
    $this->assertNull($history->getMessage());
    $this->assertNull($history->getSender());
    $this->assertNull($history->getStatus());
    $this->assertNull($history->getOwner());
    $this->assertNull($history->getExpiry());

    $history
      ->addSmsMessage($this->randomSmsMessage(NULL))
      ->addSmsMessage($this->randomSmsMessage(NULL));
    $this->assertEquals(2, count($history->getSmsMessages()));
    $history->save();
    $this->assertEquals(2, count($history->getSmsMessages()));

    // Delete the underlying SMS messages.
    foreach (SmsMessage::loadMultiple() as $sms_message) {
      $sms_message->delete();
    }
    $this->assertEquals(0, count(SmsMessage::loadMultiple()));
    $history = SmsHistory::load($history->id());
    $this->assertEquals([], $history->getSmsMessages());
    $this->assertEquals([], $history->getResults());
    $this->assertEquals([], $history->getReports());
    $this->assertNull($history->getMessage());
    $this->assertNull($history->getSender());
    $this->assertNull($history->getStatus());
    $this->assertNull($history->getOwner());
    $this->assertNull($history->getExpiry());

    $history->delete();
    $this->assertNull(SmsHistory::load($history->id()));
  }

  /**
   * @covers ::addSmsMessage
   */
  public function testAddMessage() {
    $history = SmsHistory::create();
    $this->assertEquals([], $history->getSmsMessages());
    User::create(['uid' => 7, 'name' => 'user'])->save();
    $message1 = (SmsMessage::create())
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers())
      ->setSender($this->randomMachineName())
      ->setSenderEntity(User::load(7));
    $history->addSmsMessage($message1);
    $this->assertEquals(1, count($history->getSmsMessages()));
    $this->assertEquals($message1->getRecipients(), $history->getRecipients());
    $count1 = count($message1->getRecipients());
    $this->assertEquals($count1, count($history->getRecipients()));
    $this->assertEquals($message1->getSenderEntity(), $history->getOwner());

    // Add another message.
    $message2 = (SmsMessage::create())
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers())
      ->setSender($this->randomMachineName())
      ->setSenderEntity(User::load(7));
    $history->addSmsMessage($message2);
    $this->assertEquals(2, count($history->getSmsMessages()));
    $this->assertEquals(array_merge($message1->getRecipients(), $message2->getRecipients()), $history->getRecipients());
    $count2 = count($message2->getRecipients());
    $this->assertEquals($count1 + $count2, count($history->getRecipients()));

    // Add a third message after save.
    $history->save();
    $history = SmsHistory::load($history->id());
    $message3 = (SmsMessage::create())
      ->setMessage($this->randomString())
      ->addRecipients($this->randomPhoneNumbers())
      ->setSender($this->randomMachineName())
      ->setSenderEntity(User::load(7));
    $history->addSmsMessage($message3);
    $this->assertEquals(3, count($history->getSmsMessages()));
    $this->assertEquals(array_merge($message1->getRecipients(), $message2->getRecipients(), $message3->getRecipients()),
      $history->getRecipients());
    $count3 = count($message3->getRecipients());
    $this->assertEquals($count1 + $count2 + $count3, count($history->getRecipients()));
  }

  /**
   * @covers ::deleteSmsMessages
   */
  public function testDeleteMessages() {
    $history = SmsHistory::create();
    $this->assertEquals(0, count($history->getSmsMessages()));

    $message_text = $this->getRandomGenerator()->paragraphs();
    $sender = $this->randomMachineName();
    $count = rand(3, 20);
    $messages = [];
    for ($i = 0; $i < $count; $i++) {
      $message = SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL))
        ->setMessage($message_text)
        ->setSender($sender);
      $messages[] = $message;
    }
    $history
      ->setSmsMessages($messages)
      ->save();

    $this->assertEquals($count, count($history->getSmsMessages()));
    $history->deleteSmsMessages();
    $this->assertEquals(0, count($history->getSmsMessages()));
    // Verify that actual message entities are deleted.
    foreach ($messages as $message) {
      $this->assertNull(SmsMessage::load($message->id()));
    }
  }

  public function testDeleteHook() {
    $history = SmsHistory::create();
    $this->assertEquals(0, count($history->getSmsMessages()));

    $message_text = $this->getRandomGenerator()->paragraphs();
    $sender = $this->randomMachineName();
    $count = rand(3, 20);
    $messages = [];
    for ($i = 0; $i < $count; $i++) {
      $message = SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL))
                           ->setMessage($message_text)
                           ->setSender($sender);
      $messages[] = $message;
    }
    $history
      ->setSmsMessages($messages)
      ->save();

    $this->assertEquals(count($messages), count(SmsMessage::loadMultiple()));
    $history->delete();
    $this->assertEquals([], SmsMessage::loadMultiple());
  }

  /**
   * Tests getResults and getReports with none set.
   *
   * @covers ::getResults
   * @covers ::getReports
   */
  public function testGetReportsNoResult() {
    $message = SmsMessage::convertFromSmsMessage($this->randomSmsMessage(NULL))
      ->setMessage($this->randomString())
      ->setSender('test sender');
    $history = SmsHistory::create()->setSmsMessages([$message]);
    $this->assertEquals([], $history->getResults(), 'Empty results array if result not set');
    $this->assertEquals([], $history->getReports(), 'Empty reports array if reports not set');
  }

}
