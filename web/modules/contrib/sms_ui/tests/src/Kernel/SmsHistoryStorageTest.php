<?php

namespace Drupal\Tests\sms_ui\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Direction;
use Drupal\sms\Event\SmsEvents;
use Drupal\sms\Event\SmsMessageEvent;
use Drupal\sms\Tests\SmsFrameworkTestTrait;
use Drupal\sms_ui\Entity\SmsHistory;
use Drupal\sms_ui\EventSubscriber\HistoryEventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides tests for SmsHistory interactions with smsframework dispatch system.
 *
 * @group sms_ui
 */
class SmsHistoryStorageTest extends KernelTestBase {

  use SmsFrameworkTestTrait;

  public static $modules = ['user', 'sms', 'telephone', 'dynamic_entity_reference', 'sms_ui', 'sms_test_gateway'];

  /**
   * The default SMS provider service.
   *
   * @var \Drupal\sms\Provider\SmsProviderInterface
   */
  protected $defaultSmsProvider;

  /**
   * @var \Drupal\Core\Cron
   */
  protected $cronService;

  /**
   * @var \Drupal\Tests\sms_ui\Kernel\TestHistoryEventSubscriber
   */
  protected $historySubscriber;

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
    $this->defaultSmsProvider = $this->container->get('sms.provider');
    $this->cronService = $this->container->get('cron');
    $this->historySubscriber = new TestHistoryEventSubscriber($this->container->get('request_stack'),
      $this->container->get('config.factory'));
    $this->container->set('sms_ui.history_subscriber', $this->historySubscriber);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Add a dummy event subscriber to be used later.
    $container->register('test_splitter', TestSmsSplitter::class)
              ->addTag('event_subscriber');
  }

  /**
   * Tests messages that are sent directly and those queued before sending.
   *
   * @dataProvider providerSkipQueueAndHistory
   */
  public function testSkipQueueAndHistory($skip_queue, $first_state, $second_state) {
    $gateway = $this->createMemoryGateway();
    $gateway->setSkipQueue($skip_queue)->save();
    $sms_message = $this->randomSmsMessage(NULL)
                        ->setDirection(Direction::OUTGOING)
                        ->setGateway($gateway);
    $this->defaultSmsProvider->queue($sms_message);
    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface[] $histories */
    $histories = array_values(SmsHistory::loadMultiple());
    $this->assertEquals(1, count($histories));
    $this->assertEquals($first_state, $histories[0]->getStatus());
    $this->assertEquals(count($sms_message->getRecipients()), count($histories[0]->getRecipients()));

    $this->cronService->run();
    $histories = array_values(SmsHistory::loadMultiple());
    $this->assertEquals(1, count($histories));
    $this->assertEquals($second_state, $histories[0]->getStatus());
    $this->assertEquals(count($sms_message->getRecipients()), count($histories[0]->getRecipients()));
  }

  /**
   * Data provider for testSkipQueueAndHistory
   */
  public function providerSkipQueueAndHistory() {
    return [
      [FALSE, 'queued', 'sent'],
      [TRUE, 'sent', 'sent'],
    ];
  }

  /**
   * This test verifies the correct behavior when messages are split and
   * sent to two different gateways, one with skip_queue on and the other off.
   */
  public function testQueueAndSendHistory() {
    // Send directly and verify that it is saved.
    $gateway1 = $this->createMemoryGateway();
    $gateway1->setSkipQueue(FALSE)->save();
    $gateway2 = $this->createMemoryGateway();
    $gateway2->setSkipQueue(FALSE)->save();

    // Create an even subscriber that will chunk and split messages
    $this->container->get('test_splitter')
                    ->setGateways([$gateway1, $gateway2])
                    ->setStatus(TRUE);

    // Create and queue a message and verify it is in the queue list.
    $sms_message = $this->randomSmsMessage(NULL)->setDirection(Direction::OUTGOING);
    $this->defaultSmsProvider->queue($sms_message);
    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface[] $histories1 */
    $histories1 = SmsHistory::loadMultiple();
    $first_history = reset($histories1);
    $this->assertEquals(1, count($histories1));
    $this->assertEquals(2, count($first_history->getSmsMessages()));
    $this->assertEquals(count($sms_message->getRecipients()), count($first_history->getRecipients()));

    // Dispatch the queued messages and verify they are still there.
    $this->cronService->run();
    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface[] $histories2 */
    $histories2 = SmsHistory::loadMultiple();
    $first_history = reset($histories2);
    $this->assertEquals(1, count($histories2));
    $this->assertEquals(2, count($first_history->getSmsMessages()));
    $this->assertEquals(count($sms_message->getRecipients()), count($first_history->getRecipients()));
  }

  /**
   * This test verifies the correct behavior when messages are split and
   * sent to two different gateways using SmsProviderInterface::send() method
   * directly.
   */
  public function _testSendWithoutQueueHistory() {
    // Send directly and verify that it is saved.
    $gateway1 = $this->createMemoryGateway();
    $gateway1->setSkipQueue(FALSE)->save();
    $gateway2 = $this->createMemoryGateway();
    $gateway2->setSkipQueue(FALSE)->save();

    // Create an even subscriber that will chunk and split messages
    $this->container->get('test_splitter')
                    ->setGateways([$gateway1, $gateway2])
                    ->setStatus(TRUE);

    // Create and send a message directly and verify it is in the queue list.
    $sms_message = $this->randomSmsMessage(NULL)->setDirection(Direction::OUTGOING);
    $this->defaultSmsProvider->send($sms_message);
    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface[] $histories1 */
    $histories = SmsHistory::loadMultiple();
    $first_history = reset($histories);
    $this->assertEquals(1, count($histories));
    $this->assertEquals(2, count($first_history->getSmsMessages()));
    $this->assertEquals(count($sms_message->getRecipients()), count($first_history->getRecipients()));
  }

  /**
   * Tests that the SMS History is cleaned up after it has expired.
   */
  public function testHistoryCronCleanup() {
    // Send directly and verify that it is saved.
    $gateway = $this->createMemoryGateway();

    // Create and send a message directly and verify the history exists.
    $sms_message = $this->randomSmsMessage(NULL)
                        ->setDirection(Direction::OUTGOING)
                        ->setGateway($gateway);

    // Directly set default expiry date on the history subscriber. We can't set
    // this via retention time because retention is specified in days.
    $this->historySubscriber->setDefaultExpiry(time() + 10);

    $this->defaultSmsProvider->send($sms_message);
    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface[] $histories1 */
    $histories1 = SmsHistory::loadMultiple();
    $first_history1 = reset($histories1);
    $this->assertEquals(1, count($histories1));
    $this->assertEquals(1, count($first_history1->getSmsMessages()));
    $this->assertEquals(count($sms_message->getRecipients()), count($first_history1->getRecipients()));

    // Create and send a second message directly and verify the second history
    // is added. We need to clear the stored history from the event subscriber.
    $sms_message = $this->randomSmsMessage(NULL)
                        ->setDirection(Direction::OUTGOING)
                        ->setGateway($gateway);
    $this->historySubscriber->resetCurrentHistory();
    $this->historySubscriber->setDefaultExpiry(time() + 50);
    $this->defaultSmsProvider->send($sms_message);

    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface[] $histories2 */
    $histories2 = SmsHistory::loadMultiple();
    $first_history2 = reset($histories2);
    // There should be two history items now.
    $this->assertEquals(2, count($histories2));
    $this->assertEquals($first_history1->getSmsMessages(), $first_history2->getSmsMessages());

    // Wait 10 seconds, run cron, then confirm that one history item is deleted.
    $start = time();
    while (time() - $start < 10) {}

    $this->cronService->run();
    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface[] $histories2 */
    $histories3 = SmsHistory::loadMultiple();
    $first_history3 = reset($histories3);
    $this->assertEquals(1, count($histories3));

    // Wait another 40 seconds, run cron, then confirm that the second history
    // item is deleted.
    $start = time();
    while (time() - $start < 40) {}

    $this->cronService->run();
    /** @var \Drupal\sms_ui\Entity\SmsHistoryInterface[] $histories2 */
    $histories4 = SmsHistory::loadMultiple();
    $first_history4 = reset($histories4);
    $this->assertEquals(0, count($histories4));
    $this->assertFalse($first_history4);
  }

}

/**
 * A test SMS provider that splits message recipients to different gateways.
 */
class TestSmsSplitter implements EventSubscriberInterface {

  protected $gateways = [];

  protected $status = false;

  /**
   * Constructs a new SMS message splitter.
   *
   * @param \Drupal\sms\Entity\SmsGatewayInterface[] $gateways
   *   The gateways for this entity to use.
   *
   * @return static
   *   For chaining.
   */
  public function setGateways(array $gateways) {
    $this->gateways = array_values($gateways);
    return $this;
  }

  /**
   * Enables or disables the splitter by setting status.
   *
   * @param boolean $status
   *   The new status.
   *
   * @return static
   *   For chaining.
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * Splits the SMS messages among the specified gateways.
   */
  public function splitSmsMessages(SmsMessageEvent $event) {
    if ($this->status) {
      // Split the SMS messages into multiple sets with different gateways.
      $return = [];
      foreach ($event->getMessages() as $sms_message) {
        $blank = clone $sms_message;
        $blank->removeRecipients($blank->getRecipients());
        foreach ($this->gateways as $index => $gateway) {
          $clone = clone $blank;
          foreach ($sms_message->getRecipients() as $index2 => $recipient) {
            if ($index2 % count($this->gateways) == $index) {
              $clone->addRecipient($recipient);
            }
          }
          $return[] = $clone->setGateway($gateway);
        }
      }
      $event->setMessages($return);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SmsEvents::MESSAGE_PRE_PROCESS][] = ['splitSmsMessages', 1200];
    return $events;
  }

}

class TestHistoryEventSubscriber extends HistoryEventSubscriber {

  public function resetCurrentHistory() {
    $this->currentHistory = NULL;
  }

  public function setDefaultExpiry($expiry) {
    $this->expiry = $expiry;
  }

}
