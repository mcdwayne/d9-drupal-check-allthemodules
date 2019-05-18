<?php

namespace Drupal\Tests\sender\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\ConfigFactory;
use Psr\Log\LoggerInterface;
use Drupal\sender\Plugin\SenderMethod\SenderMethodInterface;
use Drupal\sender\Entity\Message;
use Drupal\sender\Sender;

/**
 * @coversDefaultClass \Drupal\sender\Sender
 * @group sender
 */
class SenderTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sender'];

  protected $logger;
  protected $renderer;
  protected $moduleHandler;
  protected $senderMethod;
  protected $service;
  protected $message;
  protected $renderedMessage;
  protected $expectedData;
  protected $recipient;
  protected $messageQueue;
  protected $settings;
  protected $currentUser;

  public function testGetService() {
    $service = \Drupal::service('sender.sender');

    $this->assertInstanceOf(\Drupal\sender\SenderInterface::class, $service);
  }

  public function testSendMessageObject() {
    $this->senderMethod->expects($this->once())
                       ->method('send')
                       ->with($this->expectedData, $this->recipient, $this->message);

    $this->service->send($this->message, $this->recipient);
  }

  public function testSendMessageById() {
    // @todo
  }

  public function testSendMessageInvalidId() {
    try {
      $this->service->send('inexistent_id', $this->recipient);
    }
    catch (\InvalidArgumentException $e) {
    }

    $this->assertInstanceOf(\InvalidArgumentException::class, $e);
  }

  public function testSendMessageMultipleRecipients() {
    $recipients = [
      $this->recipient,
      $this->createUser(),
      $this->createUser(),
    ];

    $this->senderMethod->expects($this->exactly(3))
                       ->method('send')
                       ->withConsecutive(
                          [$this->expectedData, $recipients[0], $this->message],
                          [$this->expectedData, $recipients[1], $this->message],
                          [$this->expectedData, $recipients[2], $this->message]
                       );

    $this->service->send($this->message, $recipients);
  }

  public function testSendMessageCurrentUser() {
    $this->senderMethod->expects($this->once())
                       ->method('send')
                       ->with($this->expectedData, $this->currentUser, $this->message);

    $this->service->send($this->message);
  }

  public function testSendMessageNoRecipients() {
    $this->senderMethod->expects($this->never())
                       ->method('send');
    $this->logger->expects($this->once())
                 ->method('warning');

    $this->service->send($this->message, []);
  }

  public function testSendMessageSpecificMethod() {
    // @todo
  }

  public function testSendMessageNoMethods() {
    $this->senderMethod->expects($this->never())
                       ->method('send');
    $this->logger->expects($this->atLeastOnce())
                 ->method('error');

    $this->service->send($this->message, $this->recipient, [], ['inexistent_method']);
  }

  public function testMessageIsBuilt() {
    // @todo
  }

  public function testMessageIsRendered() {
    $this->renderer->expects($this->once())
                   ->method('renderRoot')
                   ->with($this->contains('sender_message'));

    $this->service->send($this->message, $this->recipient);
  }

  public function testMethodIsAddedToRenderArray() {
    $this->renderer->expects($this->once())
                   ->method('renderRoot')
                   ->with($this->arrayHasKey('#method'));

    $this->service->send($this->message, $this->recipient);
  }

  public function testAlterHooksInvoked() {
    $this->moduleHandler->expects($this->at(0))
                        ->method('alter')
                        ->with(
                          'sender_methods',
                          [$this->senderMethod->id()],
                          $this->message,
                          NULL
                        );
    $this->moduleHandler->expects($this->at(1))
                        ->method('alter')
                        ->with(
                          'sender_recipients',
                          [$this->recipient],
                          $this->message,
                          [$this->senderMethod->id()]
                        );
    $this->moduleHandler->expects($this->at(2))
                        ->method('alter')
                        ->with(
                          'sender_message_data',
                          $this->expectedData,
                          $this->message,
                          ['recipient' => $this->recipient, 'method' => $this->senderMethod]
                        );

    $this->service->send($this->message, $this->recipient);
  }

  public function testMessageEnqueued() {
    // Turns message enqueuing on.
    $this->settings['queue_on'] = 1; 

    // Expected queue item.
    $item = [
      'data' => $this->expectedData,
      'recipient_id' => $this->recipient->id(),
      'message_id' => $this->message->id(),
      'method_id' => $this->senderMethod->id(),
    ];

    $this->messageQueue->expects($this->once())
                       ->method('createItem')
                       ->with($item);

    $this->service->send($this->message, $this->recipient);
  }

  protected function setUp() {
    parent::setUp();

    // Mocks the renderer.
    $this->renderedMessage = 'This is the rendered message.';
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->renderer->method('renderRoot')
                   ->willReturn($this->renderedMessage);

    // Mocks the logger and logger factory.
    $this->logger = $this->createMock(LoggerInterface::class);
    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')
                   ->will($this->returnValueMap([['sender', $this->logger]]));

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

    // Mocks a module handler.
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    // Mocks a queue and queue factory.
    $this->messageQueue = $this->createMock(QueueInterface::class);
    $queue_factory = $this->createMock(QueueFactory::class);
    $queue_factory->method('get')
                  ->willReturnMap([
                    ['sender_message_queue', FALSE, $this->messageQueue],
                  ]);

    // Mocks a configuration and configuration factory.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
           ->willReturnCallback([$this, 'getSetting']);
    $config_factory = $this->createMock(ConfigFactory::class);
    $config_factory->method('get')
                   ->willReturnMap([
                    ['sender.settings', $config],
                   ]);

    // Creates a user to be the current user.
    $this->currentUser = $this->createUser();

    // Instatiates the service to test.
    $this->service = new Sender($plugin_manager, $this->renderer, $logger_factory, 
      $this->moduleHandler, $queue_factory, $config_factory, $this->currentUser);

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

    // The expected data for the message.
    $this->expectedData = [
      'subject' => $this->message->getSubject(),
      'body' => $this->message->getBody(),
      'rendered' => $this->renderedMessage, 
    ];

    // Creates a recipient for the message.
    $this->recipient = $this->createUser();
  }

  /**
   * Returns a module setting.
   */
  public function getSetting($key = '') {
    return $this->settings[$key] ?: NULL;
  }
}
