<?php

namespace Drupal\Tests\sender\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Drupal\sender\Entity\Message;
use Drupal\sender\Plugin\SenderMethod\SenderMethodInterface;
use Drupal\sender\Plugin\QueueWorker\MessageQueueWorker;

/**
 * @coversDefaultClass \Drupal\sender\Plugin\QueueWorker\MessageQueueWorker
 * @group sender
 */
class MessageQueueWorkerTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sender'];

  protected $queueWorker;
  protected $logger;
  protected $senderMethod;
  protected $message;
  protected $expectedData;
  protected $recipient;
  protected $queueItem;

  public function testMessageSent() {
    // The loaded entities differ from the created ones.
    $recipient = User::load($this->recipient->id());
    $message = Message::load($this->message->id());

    $this->senderMethod->expects($this->once())
                       ->method('send')
                       ->with($this->expectedData, $recipient, $message);

    $this->queueWorker->processItem($this->queueItem);
  }

  protected function setUp() {
    parent::setUp();

    // Mocks the logger and logger factory.
    $this->logger = $this->createMock(LoggerInterface::class);
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')
                   ->will($this->returnValueMap([['sender', $this->logger]]));

    // Mocks a translation service.
    $string_translation = $this->createMock(TranslationInterface::class);

    // Mocks a sender method.
    $sender_method_id = 'sender_mock';
    $this->senderMethod = $this->createMock(SenderMethodInterface::class);
    $this->senderMethod->method('id')
                       ->willReturn($sender_method_id);

    // Mocks the plugin manager used to instantiate sender methods.
    $plugin_manager = $this->createMock(PluginManagerInterface::class);
    $plugin_manager->expects($this->any())
                   ->method('getDefinitions')
                   ->willReturn([$sender_method_id => ['id' => $sender_method_id]]);
    $plugin_manager->expects($this->any())
                   ->method('createInstance')
                   ->willReturnMap([
                     [$sender_method_id, [], $this->senderMethod],
                   ]);

    // Instatiates the queue worker to test.
    $plugin_id = 'sender_message_queue';
    $plugin_definition = [];
    $this->queueWorker = new MessageQueueWorker([], $plugin_id, $plugin_definition,
      $plugin_manager, $string_translation, $logger_factory);

    // Creates a message.
    $values = [
      'id' => 'test_message',
      'subject' => 'Test message',
      'body' => [
        'value' => 'Some text',
        'format' => 'full_html',
      ],
    ];
    $this->message = Message::create($values);
    $this->message->save();

    // The expected data for the message.
    $this->expectedData = [
      'subject' => $this->message->getSubject(),
      'body' => $this->message->getBody(),
      'rendered' => 'rendered message', 
    ];

    // Creates a recipient for the message.
    $this->recipient = $this->createUser();
    $this->recipient->save();

    // Creates a queue item to be processed.
    $this->queueItem = [
      'data' => $this->expectedData,
      'message_id' => $this->message->id(),
      'recipient_id' => $this->recipient->id(),
      'method_id' => $this->senderMethod->id(),
    ];
  }
}
