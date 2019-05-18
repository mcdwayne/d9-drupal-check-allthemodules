<?php

namespace Drupal\Tests\authorization_code_sms\Kernel\Plugin\CodeSender;

use Drupal\authorization_code_sms\Plugin\CodeSender\Sms;
use Drupal\Core\Utility\Token;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Exception\NoPhoneNumberException;
use Drupal\sms\Exception\SmsPluginReportException;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Provider\PhoneNumberProviderInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests for the SMS code sender plugin.
 *
 * @group authorization_code
 */
class SmsTest extends KernelTestBase {

  const TEST_CODE = '012345';
  const MESSAGE_TEMPLATE = 'Code: [authorization_code:code]';
  const MESSAGE = 'Code: 012345';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'sms',
    'authorization_code_sms',
  ];

  /**
   * Mock of Token service.
   *
   * @var \Drupal\Core\Utility\Token|\PHPUnit_Framework_MockObject_MockObject
   */
  private $tokenService;

  /**
   * Mock of the phone number provider service.
   *
   * @var \Drupal\sms\Provider\PhoneNumberProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $phoneNumberProvider;

  /**
   * Mock user.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->testUser = $this->createMock(UserInterface::class);

    $this->phoneNumberProvider = $this->createMock(PhoneNumberProviderInterface::class);
    ($this->tokenService = $this->createMock(Token::class))
      ->method('replace')
      ->with(static::MESSAGE_TEMPLATE, [
        'user' => $this->testUser,
        'authorization_code' => static::TEST_CODE,
      ])
      ->willReturn(static::MESSAGE);

    $this->container->set('sms.phone_number', $this->phoneNumberProvider);
    $this->container->set('token', $this->tokenService);
    $this->container->set('logger.channel.authorization_code', $this->createMock(LoggerInterface::class));
  }

  /**
   * Test NoPhoneNumberException is thrown.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   */
  public function testNoPhoneNumberException() {
    $this->phoneNumberProvider
      ->expects($this->once())
      ->method('sendMessage')
      ->with($this->testUser, $this->callback([$this, 'validateMessage']))
      ->willThrowException(new NoPhoneNumberException());

    $this->createPlugin()->sendCode($this->testUser, static::TEST_CODE);
  }

  /**
   * Test SmsPluginReportException is thrown.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   */
  public function testSmsPluginReportException() {
    $this->phoneNumberProvider
      ->expects($this->once())
      ->method('sendMessage')
      ->with($this->testUser, $this->callback([$this, 'validateMessage']))
      ->willThrowException(new SmsPluginReportException());

    $this->createPlugin()->sendCode($this->testUser, static::TEST_CODE);
  }

  /**
   * Test when message is sent.
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   */
  public function testMessageSent() {
    $this->phoneNumberProvider
      ->expects($this->once())
      ->method('sendMessage')
      ->with($this->testUser, $this->callback([$this, 'validateMessage']));

    $this->createPlugin()->sendCode($this->testUser, static::TEST_CODE);
  }

  /**
   * Validates the message argument.
   *
   * @param mixed $arg
   *   The argument.
   *
   * @return bool
   *   Is this a valid message.
   */
  public function validateMessage($arg): bool {
    return ($arg instanceof SmsMessageInterface) && $arg->getMessage() == static::MESSAGE;
  }

  /**
   * Creates SMS plugin instance.
   *
   * @return \Drupal\authorization_code_sms\Plugin\CodeSender\Sms
   *   The plugin.
   */
  private function createPlugin(): Sms {
    return Sms::create(
      $this->container,
      [
        'plugin_id' => 'sms',
        'settings' => ['message_template' => static::MESSAGE_TEMPLATE],
      ],
      'sms',
      []);
  }

}
