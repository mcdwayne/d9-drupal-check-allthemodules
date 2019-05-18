<?php

namespace Drupal\Tests\authorization_code\Unit;

use Drupal\authorization_code\CodeGeneratorInterface;
use Drupal\authorization_code\CodeRepository;
use Drupal\authorization_code\CodeSenderInterface;
use Drupal\authorization_code\Entity\LoginProcess;
use Drupal\authorization_code\Exceptions\FailedToSaveCodeException;
use Drupal\authorization_code\Exceptions\FailedToSendCodeException;
use Drupal\authorization_code\PluginManager;
use Drupal\authorization_code\UserIdentifierInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use phpmock\phpunit\PHPMock;
use Psr\Log\LoggerInterface;

/**
 * Login process test.
 *
 * @group authorization_code
 */
class LoginProcessTest extends UnitTestCase {

  use PHPMock;

  const TEST_CODE = '0123';
  const FLOOD_THRESHOLD = 2;
  const FLOOD_WINDOW = 10;

  /**
   * Mock of config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $configFactory;

  /**
   * Mock of flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $flood;

  /**
   * Mock of code repository service.
   *
   * @var \Drupal\authorization_code\CodeRepository|\PHPUnit_Framework_MockObject_MockObject
   */
  private $codeRepository;

  /**
   * Mock of code generator plugin manager service.
   *
   * @var \Drupal\authorization_code\PluginManager|\PHPUnit_Framework_MockObject_MockObject
   */
  private $codeGeneratorManager;

  /**
   * Mock of code sender plugin manager service.
   *
   * @var \Drupal\authorization_code\PluginManager|\PHPUnit_Framework_MockObject_MockObject
   */
  private $codeSenderManager;

  /**
   * Mock of user identifier plugin manager service.
   *
   * @var \Drupal\authorization_code\PluginManager|\PHPUnit_Framework_MockObject_MockObject
   */
  private $userIdentifierManager;

  /**
   * Mock of user identifier plugin.
   *
   * @var \Drupal\authorization_code\UserIdentifierInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $userIdentifier;

  /**
   * Mock of code sender plugin.
   *
   * @var \Drupal\authorization_code\CodeSenderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $codeSender;

  /**
   * Mock of code generator plugin.
   *
   * @var \Drupal\authorization_code\CodeGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $codeGenerator;

  /**
   * Mock of a user.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $testUser;

  /**
   * Mock of the container object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  private $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->flood = $this->createMock(FloodInterface::class);
    $this->configFactory = $this->getConfigFactoryStub([
      'authorization_code.settings' => [
        'ip_flood_threshold' => static::FLOOD_THRESHOLD,
        'ip_flood_window' => static::FLOOD_WINDOW,
        'user_flood_threshold' => static::FLOOD_THRESHOLD,
        'user_flood_window' => static::FLOOD_WINDOW,
      ],
    ]);
    $this->userIdentifierManager = $this->createMock(PluginManager::class);
    $this->codeGeneratorManager = $this->createMock(PluginManager::class);
    $this->codeSenderManager = $this->createMock(PluginManager::class);

    $this->codeRepository = $this->createMock(CodeRepository::class);

    $this->userIdentifier = $this->createMock(UserIdentifierInterface::class);
    $this->codeGenerator = $this->createMock(CodeGeneratorInterface::class);
    $this->codeSender = $this->createMock(CodeSenderInterface::class);

    $this->container = new ContainerBuilder();
    $this->container->set('flood', $this->flood);
    $this->container->set('config.factory', $this->configFactory);
    $this->container->set('plugin.manager.user_identifier', $this->userIdentifierManager);
    $this->container->set('plugin.manager.code_generator', $this->codeGeneratorManager);
    $this->container->set('plugin.manager.code_sender', $this->codeSenderManager);
    $this->container->set('authorization_code.code_repository', $this->codeRepository);
    $this->container->set('logger.channel.authorization_code', $this->createMock(LoggerInterface::class));
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests startLoginProcess when user identifier not found.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testStartLoginProcessWhenUserIdentifierNotFound() {
    $this->setFloodGates();
    $this->userIdentifierManager
      ->method('createInstance')
      ->willThrowException(new PluginNotFoundException('test_user_identifier'));

    $this->createLoginProcess()->startLoginProcess('test_user');
  }

  /**
   * Tests completeLoginProcess when user identifier not found.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testCompleteLoginProcessWhenUserIdentifierNotFound() {
    $this->setFloodGates();
    $this->userIdentifierManager
      ->method('createInstance')
      ->willThrowException(new PluginNotFoundException('test_user_identifier'));
    $this->createLoginProcess()
      ->completeLoginProcess('test_user', 'invalid_code');
  }

  /**
   * Tests startLoginProcess when code generator not found.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testStartLoginProcessWhenCodeGeneratorNotFound() {
    $this->setFloodGates();
    $this->registerUserIdentifierPlugin();
    $this->codeGeneratorManager->method('createInstance')
      ->willThrowException(new PluginException());

    $this->createLoginProcess()->startLoginProcess('test_user');
  }

  /**
   * Tests startLoginProcess when code sender not found.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testStartLoginProcessWhenCodeSenderNotFound() {
    $this->setFloodGates();
    $this->registerUserIdentifierPlugin();
    $this->registerCodeGeneratorPlugin();
    $this->codeSenderManager->method('createInstance')
      ->willThrowException(new PluginException());

    $this->createLoginProcess()->startLoginProcess('test_user');
  }

  /**
   * Tests startLoginProcess when user is missing.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\UserNotFoundException
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testStartLoginProcessWhenUserMissing() {
    $this->setFloodGates();
    $this->registerUserIdentifierPlugin();
    $this->createLoginProcess()->startLoginProcess('missing_user');
  }

  /**
   * Tests startLoginProcess when code save failed.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testStartLoginProcessWhenSaveFailed() {
    $this->setFloodGates();
    $this->registerUserIdentifierPlugin();
    $this->registerCodeGeneratorPlugin();
    $this->registerCodeSenderPlugin();
    $this->codeRepository
      ->method('saveCode')
      ->with($this->isInstanceOf(LoginProcess::class), $this->testUser, static::TEST_CODE)
      ->willThrowException(new FailedToSaveCodeException());
    $this->createLoginProcess()->startLoginProcess('test_user');
  }

  /**
   * Tests startLoginProcess when code send failed.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testStartLoginProcessWhenSendFailed() {
    $this->setFloodGates();
    $this->registerUserIdentifierPlugin();
    $this->registerCodeGeneratorPlugin();
    $this->registerCodeSenderPlugin();
    $this->codeRepository
      ->method('saveCode')
      ->with($this->isInstanceOf(LoginProcess::class), $this->testUser, static::TEST_CODE);
    $this->codeSender
      ->method('sendCode')
      ->with($this->testUser, static::TEST_CODE)
      ->willThrowException(new FailedToSendCodeException($this->testUser));
    $this->createLoginProcess()->startLoginProcess('test_user');
  }

  /**
   * Tests startLoginProcess when all is well.
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSaveCodeException
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testStartLoginProcess() {
    $this->setFloodGates();
    $this->registerUserIdentifierPlugin();
    $this->registerCodeGeneratorPlugin();
    $this->registerCodeSenderPlugin();
    $this->codeSender->expects($this->once())->method('sendCode');
    $this->codeRepository
      ->expects($this->once())
      ->method('saveCode')
      ->with($this->isInstanceOf(LoginProcess::class), $this->testUser, static::TEST_CODE);
    $this->createLoginProcess()->startLoginProcess('test_user');
  }

  /**
   * Tests completeLoginProcess when code is invalid.
   *
   * @expectedException \Drupal\authorization_code\Exceptions\InvalidCodeException
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testCompleteLoginProcessWhenInvalidCode() {
    $this->setFloodGates();
    $this->registerUserIdentifierPlugin();
    $this->codeRepository->method('isValidCode')->willReturn(FALSE);
    $this->createLoginProcess()
      ->completeLoginProcess('test_user', 'invalid_code');
  }

  /**
   * Test completeLoginProcess when all is well.
   *
   * @throws \Drupal\authorization_code\Exceptions\InvalidCodeException
   * @throws \Drupal\authorization_code\Exceptions\InvalidLoginProcessConfiguration
   * @throws \Drupal\authorization_code\Exceptions\IpFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserFloodException
   * @throws \Drupal\authorization_code\Exceptions\UserNotFoundException
   */
  public function testCompleteLoginProcess() {
    $this->setFloodGates();
    $this->registerUserIdentifierPlugin();
    $this->codeRepository->method('isValidCode')
      ->with($this->isInstanceOf(LoginProcess::class), $this->testUser, static::TEST_CODE)
      ->willReturn(TRUE);
    $this->getFunctionMock('Drupal\authorization_code\Entity', 'user_login_finalize')
      ->expects($this->once())
      ->with($this->testUser);
    $this->createLoginProcess()
      ->completeLoginProcess('test_user', static::TEST_CODE);
  }

  /**
   * Is flood gate open?
   *
   * @param bool $is_ip_allowed
   *   Is flood gate open?
   * @param bool $is_test_user_allowed
   *   Is flood gate open?
   * @param bool $is_missing_user_allowed
   *   Is flood gate open?
   */
  private function setFloodGates($is_ip_allowed = TRUE, $is_test_user_allowed = TRUE, $is_missing_user_allowed = TRUE) {
    $this->flood->method('isAllowed')
      ->will($this->returnValueMap([
        [
          'authorization_code.failed_login_ip',
          static::FLOOD_THRESHOLD,
          static::FLOOD_WINDOW,
          NULL,
          $is_ip_allowed,
        ],
        [
          'authorization_code.failed_login_user',
          static::FLOOD_THRESHOLD,
          static::FLOOD_WINDOW,
          'test_login_process:test_user',
          $is_test_user_allowed,
        ],
        [
          'authorization_code.failed_login_user',
          static::FLOOD_THRESHOLD,
          static::FLOOD_WINDOW,
          'test_login_process:missing_user',
          $is_missing_user_allowed,
        ],
      ]));
  }

  /**
   * Registers the user identifier plugin with the plugin manager mock.
   */
  private function registerUserIdentifierPlugin() {
    $this->testUser = $this->createMock(UserInterface::class);
    $this->userIdentifier
      ->method('getPluginId')
      ->willReturn('test_user_identifier');
    $this->userIdentifier
      ->method('loadUser')->with($this->isType('string'))
      ->will($this->returnValueMap([['test_user', $this->testUser]]));

    $this->userIdentifierManager->method('createInstance')
      ->with('test_user_identifier', ['plugin_id' => 'test_user_identifier'])
      ->willReturn($this->userIdentifier);
  }

  /**
   * Registers the code generator plugin with the plugin manager mock.
   */
  private function registerCodeGeneratorPlugin() {
    $this->codeGenerator->method('getPluginId')
      ->willReturn('test_code_generator');
    $this->codeGenerator->method('generate')->willReturn(static::TEST_CODE);
    $this->codeGeneratorManager->method('createInstance')
      ->with('test_code_generator', ['plugin_id' => 'test_code_generator'])
      ->willReturn($this->codeGenerator);
  }

  /**
   * Registers the code sender plugin with the plugin manager mock.
   */
  private function registerCodeSenderPlugin() {
    $this->codeSender->method('getPluginId')->willReturn('test_code_sender');
    $this->codeSenderManager->method('createInstance')
      ->with('test_code_sender', [
        'plugin_id' => 'test_code_sender',
        'settings' => ['message_template' => 'test message'],
      ])
      ->willReturn($this->codeSender);
  }

  /**
   * Creates a login process entity.
   *
   * @return \Drupal\authorization_code\Entity\LoginProcess
   *   The login process entity.
   */
  private function createLoginProcess(): LoginProcess {
    return new LoginProcess([
      'id' => 'test_login_process',
      'user_identifier' => ['plugin_id' => 'test_user_identifier'],
      'code_generator' => ['plugin_id' => 'test_code_generator'],
      'code_sender' => [
        'plugin_id' => 'test_code_sender',
        'settings' => [
          'message_template' => 'test message',
        ],
      ],
    ], 'login_process');
  }

}
