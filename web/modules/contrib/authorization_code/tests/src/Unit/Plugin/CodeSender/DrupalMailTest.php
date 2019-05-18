<?php

namespace Drupal\Tests\authorization_code\Unit\Plugin\CodeSender;

use Drupal\authorization_code\Plugin\CodeSender\DrupalMail;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * Drupal mail unit tests.
 *
 * @group authorization_code
 */
class DrupalMailTest extends UnitTestCase {

  const TEST_CODE = '0123';

  const TEST_SUBJECT_TEMPLATE = 'Test subject';

  const TEST_MESSAGE_TEMPLATE = 'Test message';

  /**
   * Mock of a container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private $container;

  /**
   * Mock for the mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $mailManager;

  /**
   * Mock for a test user.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->mailManager = $this->getMockBuilder(MailManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->testUser = $this->createMock(UserInterface::class);
    $this->testUser->method('id')->willReturn(99);
    $this->testUser->method('getAccountName')->willReturn('test_user');

    $this->container = new ContainerBuilder();
    $this->container->set('plugin.manager.mail', $this->mailManager);
  }

  /**
   * Test sendCode when user has no email.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   */
  public function testSendCodeWhenMissingEmail() {
    $this->testUser->method('getEmail')->willReturn(NULL);
    $this->mailManager->expects($this->never())->method('mail');

    $codeSender = $this->createPlugin();
    $codeSender->sendCode($this->testUser, static::TEST_CODE);
  }

  /**
   * Tests when all is well.
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   */
  public function testSendCode() {
    $this->testUser->method('getEmail')->willReturn('foo@example.com');
    $this->testUser->method('getPreferredLangcode')->willReturn('foo');
    $this->mailManager
      ->expects($this->once())
      ->method('mail')
      ->with('system', $this->anything(), 'foo@example.com', 'foo',
        [
          'context' => [
            'subject' => static::TEST_SUBJECT_TEMPLATE,
            'message' => static::TEST_MESSAGE_TEMPLATE,
            'user' => $this->testUser,
            'authorization_code' => static::TEST_CODE,
          ],
        ]);

    $codeSender = $this->createPlugin();
    $codeSender->sendCode($this->testUser, static::TEST_CODE);
  }

  /**
   * Tests when Drupal mail service fails to send the email.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   */
  public function testSendCodeWhenMailFailed() {
    $this->testUser->method('getEmail')->willReturn('foo@example.com');
    $this->mailManager
      ->expects($this->once())
      ->method('mail')
      ->withAnyParameters()
      ->willThrowException(new \Exception());

    $codeSender = $this->createPlugin();
    $codeSender->sendCode($this->testUser, static::TEST_CODE);
  }

  /**
   * Create a DrupalMail plugin instance.
   *
   * @return \Drupal\authorization_code\Plugin\CodeSender\DrupalMail
   *   The plugin instance.
   */
  private function createPlugin(): DrupalMail {
    return DrupalMail::create(
      $this->container,
      [
        'plugin_id' => 'drupal_mail',
        'settings' => [
          'subject_template' => static::TEST_SUBJECT_TEMPLATE,
          'message_template' => static::TEST_MESSAGE_TEMPLATE,
        ],
      ],
      'drupal_mail',
      []);
  }

}
