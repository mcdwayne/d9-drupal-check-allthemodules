<?php

namespace Drupal\Tests\sender\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\sender\Plugin\SenderMethod\EmailMethod;
use Drupal\sender\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\sender\Plugin\SenderMethod\EmailMethod
 * @group sender
 */
class EmailMethodTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sender'];

  protected $plugin;
  protected $mailManager;
  protected $data;
  protected $recipient;
  protected $message;
  protected $expectedParams;

  public function testSendMessage() {
    $this->mailManager->expects($this->once())
                      ->method('mail')
                      ->with(
                        'sender',
                        'sender_email',
                        $this->expectedTo,
                        $this->recipient->getPreferredLangcode(),
                        $this->expectedParams,
                        NULL,
                        TRUE
                      );

    $this->plugin->send($this->data, $this->recipient, $this->message);
  }

  protected function setUp() {
    parent::setUp();

    // Mocks the mail manager service and adds to a service container.
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')
              ->will($this->returnCallback([$this, 'getService']));

    // Instatiates an email method plugin to be tested.
    $plugin_id = 'sender_email';
    $plugin_definition = ['id' => $plugin_id];
    $this->plugin = EmailMethod::create($container, [], $plugin_id, $plugin_definition);

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
    $this->data = [
      'subject' => $this->message->getSubject(),
      'body' => $this->message->getBody(),
      'rendered' => '<p>This is the rendered message.</p>', 
    ];

    // Creates a recipient for the message.
    $this->recipient = $this->createUser(['mail' => 'user@example.com']);

    // Expected "to" header passed to MailManagerInterface::mail().
    $this->expectedTo = $this->recipient->getDisplayName() . ' <' . $this->recipient->getEmail() . '>';

    // Expected params passed to MailManagerInterface::mail().
    $this->expectedParams = [
      'subject' => $this->data['subject'],
      'body' => $this->data['rendered'],
      'entity' => $this->message,
    ];
  }

  public function getService($id) {
    // Using $this->returnValueMap() did not work.
    return ($id == 'plugin.manager.mail') ? $this->mailManager : NULL;
  }
}
