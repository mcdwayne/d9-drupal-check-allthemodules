<?php

namespace Drupal\Tests\sms_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Direction;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Tests\SmsFrameworkTestTrait;
use Drupal\sms_ui\Entity\SmsHistory;

/**
 * Covers the SmsHistoryProcessor class.
 *
 * @group sms_ui
 * @coversDefaultClass \Drupal\sms_ui\SmsHistoryProcessor
 */
class SmsHistoryProcessorTest extends KernelTestBase {

  use SmsFrameworkTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['sms', 'user', 'telephone', 'dynamic_entity_reference', 'sms_ui'];

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
   * @covers ::cleanUpHistory
   */
  public function testCleanUpHistory() {
    // Create and save a message directly and verify the history exists.
    $sms_message = $this->randomSmsMessage(NULL)
                        ->setDirection(Direction::OUTGOING)
                        ->setResult(new SmsMessageResult());
    SmsHistory::create()
       ->setSmsMessages([$sms_message])
       ->setExpiry(REQUEST_TIME)
       ->save();

    // Confirm history counts.
    $histories = SmsHistory::loadMultiple();
    $first_history = reset($histories);
    $this->assertEquals(1, count($histories));
    $this->assertEquals(1, count($first_history->getSmsMessages()));
    $this->assertEquals(count($sms_message->getRecipients()), count($first_history->getRecipients()));

    // Run the SMS history processor to clean up history.
    $this->container->get('sms_ui.history_processor')->cleanUpHistory();

    // Confirm no history exists.
    $histories = SmsHistory::loadMultiple();
    $this->assertEquals([], $histories);

    // Confirm no messages exist.
    $this->assertEquals(0, count(SmsMessage::loadMultiple()));
  }

  /**
   * @covers ::removeOrphans
   */
  public function testRemoveOrphans() {
    // Create and save a message via SMS history.
    $sms_message1 = $this->randomSmsMessage(NULL)
                        ->setDirection(Direction::OUTGOING)
                        ->setResult(new SmsMessageResult());
    SmsHistory::create()
      ->setSmsMessages([$sms_message1])
      ->setExpiry(REQUEST_TIME)
      ->save();

    // Create a message and save directly.
    for ($i = 0; $i < 4; $i++) {
      $orphan_msg = SmsMessage::create()
                        ->addRecipient($this->randomMachineName())
                        ->setDirection(Direction::OUTGOING)
                        ->setResult(new SmsMessageResult());
      $orphan_msg->save();
    }

    // Assert number of messages.
    $this->assertEquals(5, count(SmsMessage::loadMultiple()));

    // Run the SMS history processor to remove orphan messages.
    $this->container->get('sms_ui.history_processor')->removeOrphans();

    // Confirm history still exists.
    $histories = SmsHistory::loadMultiple();
    $first_history = reset($histories);
    $this->assertEquals(1, count($histories));
    $this->assertEquals($sms_message1->getRecipients(), $first_history->getRecipients());

    // Confirm SMS messages left.
    $this->assertEquals(1, count(SmsMessage::loadMultiple()));
  }

}
