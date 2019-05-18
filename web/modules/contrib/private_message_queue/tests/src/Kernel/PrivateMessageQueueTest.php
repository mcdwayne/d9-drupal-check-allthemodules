<?php

namespace Drupal\Tests\private_message_queue\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\private_message\Entity\PrivateMessageThread;
use Drupal\private_message_queue\Plugin\QueueWorker\PrivateMessageQueue;
use Drupal\user\UserInterface;

/**
 * @group private_message_queue
 */
class PrivateMessageQueueTest extends EntityKernelTestBase {

  use AssertMailTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'private_message',
    'private_message_queue',
  ];

  /**
   * The private message queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $queue;

  /**
   * The private message queuer service.
   *
   * @var \Drupal\private_message_queue\Service\PrivateMessageQueuer
   */
  private $queuerService;

  /**
   * The thread manager service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageThreadManagerInterface
   */
  private $threadManager;

  /**
   * The private message queue worker.
   *
   * @var \Drupal\private_message_queue\Plugin\QueueWorker\PrivateMessageQueueWorker
   */
  private $queueWorker;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('pm_thread_access_time');
    $this->installEntitySchema('pm_thread_delete_time');
    $this->installEntitySchema('private_message');
    $this->installEntitySchema('private_message_thread');

    $this->installSchema('user', ['users_data']);

    $this->installConfig(['filter', 'private_message']);

    $this->queue = \Drupal::queue('private_message_queue');
    $this->queue->createQueue();

    $this->queuerService = \Drupal::service('private_message_queue.queuer');
    $this->threadManager = \Drupal::service('private_message.thread_manager');

    $this->queueWorker = $this->getQueueWorker();

    // Set the site name.
    \Drupal::configFactory()->getEditable('system.site')
      ->set('name', $this->t('Queue Test'))
      ->save(TRUE);
  }

  /**
   * Test that queued items are processed correctly.
   */
  public function testProcessItem() {
    $this->assertFalse($this->queue->numberOfItems());

    // Create a new user and set it as the current user.
    $owner = $this->createUser();
    \Drupal::currentUser()->setAccount($owner);

    $recipient1 = $this->createUser();
    $recipient2 = $this->createUser();
    $recipient3 = $this->createUser();

    // Test with setting a plain text message as a string.
    $this->queuerService->queue([$recipient1], 'Message 1');

    // Test with a formatted text message as an array.
    $this->queuerService->queue(
      [$recipient2, $recipient3],
      ['value' => 'Message 2', 'format' => filter_default_format()]
    );

    $this->assertEquals(2, $this->queue->numberOfItems());

    $item1 = $this->queue->claimItem();
    $this->assertEquals('Message 1', $item1->data['message']);
    $this->assertEquals(1, $item1->data['owner']->id());
    $this->assertEquals([$recipient1], $item1->data['recipients']);

    $item2 = $this->queue->claimItem();
    $this->assertEquals('Message 2', $item2->data['message']['value']);
    $this->assertEquals(1, $item2->data['owner']->id());
    $this->assertEquals([$recipient2, $recipient3], $item2->data['recipients']);

    $this->queueWorker->processItem($item1->data);
    $this->queueWorker->processItem($item2->data);

    /** @var \Drupal\private_message\Entity\PrivateMessageThread[] $threads */
    $threads = PrivateMessageThread::loadMultiple([1, 2]);

    $this->assertCount(2, $threads[1]->getMembers());
    $this->assertEquals([2, 1], array_map(function (UserInterface $member) {
      return $member->id();
    }, $threads[1]->getMembers()));

    /** @var \Drupal\private_message\Entity\PrivateMessage[] $messages */
    $messages = $threads[1]->getMessages();
    $this->assertNotNull($messages[0]);
    $this->assertEquals('Message 1', $messages[0]->getMessage());

    $this->assertCount(3, $threads[2]->getMembers());
    $this->assertEquals([3, 4, 1], array_map(function (UserInterface $member) {
      return $member->id();
    }, $threads[2]->getMembers()));

    $messages = $threads[2]->getMessages();
    $this->assertNotNull($messages[0]);
    $this->assertEquals('Message 2', $messages[0]->getMessage());

    $mails = $this->getMails();

    $this->assertCount(3, $mails);

    $this->assertEquals($recipient1->getEmail(), $mails[0]['to']);
    $this->assertEquals('Private message at Queue Test', $mails[0]['subject']);
    $this->assertContains('Message 1', $mails[0]['body']);
    $this->assertContains('/private_messages?private_message_thread=1', $mails[0]['body']);

    $this->assertEquals($recipient2->getEmail(), $mails[1]['to']);
    $this->assertEquals('Private message at Queue Test', $mails[1]['subject']);
    $this->assertContains('Message 2', $mails[1]['body']);
    $this->assertContains('/private_messages?private_message_thread=2', $mails[1]['body']);

    $this->assertEquals($recipient3->getEmail(), $mails[2]['to']);
    $this->assertEquals('Private message at Queue Test', $mails[2]['subject']);
    $this->assertContains('Message 2', $mails[2]['body']);
    $this->assertContains('/private_messages?private_message_thread=2', $mails[2]['body']);
  }

  /**
   * Creates a new queue worker service.
   */
  private function getQueueWorker() {
    $plugin_id = 'private_message_queue';

    $plugin_definition = [
      'id' => $plugin_id,
      'class' => PrivateMessageQueue::class,
      'provider' => 'private_message',
    ];

    return new PrivateMessageQueue(
      [],
      $plugin_id,
      $plugin_definition,
      \Drupal::service('private_message.service'),
      \Drupal::service('private_message.thread_manager')
    );
  }
}
