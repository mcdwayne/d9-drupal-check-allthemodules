<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 11:17 AM
 */

namespace Drupal\Tests\basicshib\Kernel;


use Drupal\basicshib\AuthenticationHandlerInterface;
use Drupal\basicshib\Exception\AuthenticationException;
use Drupal\basicshib\SessionTracker;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\basicshib\Traits\MockTrait;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthenticationHandlerTest extends KernelTestBase {
  use MockTrait;

  public static $modules = ['basicshib', 'user', 'system'];

  public function setUp() {
    parent::setUp();
    $this->installConfig(['basicshib']);
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
  }

  /**
   * @param $name
   * @param $mail
   * @return EntityInterface|UserInterface
   */
  private function createUser($name, $mail, $status = 1) {
    /** @var UserStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('user');

    $user = $storage->create([
      'name' => 'jdoe@example.com',
      'mail' => 'jdoe@mail.example.com',
      'status' => $status,
    ]);

    $user->save();

    return $user;
  }

  public function testAuthenticationWithExistingUserSucceeds() {
    $user = $this->createUser('jdoe@example.com', 'jdoe@mail.example.com');

    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => 'abcd',
      'eppn' => $user->getAccountName(),
      'mail' => $user->getEmail(),
    ]);

    $this->container->set('request_stack', $request_stack);

    $handler = $this->container->get('basicshib.authentication_handler');

    try {
      $handler->authenticate();
    }
    catch (\Exception $exception) {
      $this->fail(sprintf('An exception was thrown: %s', $exception->getTraceAsString()));
    }
  }

  public function testAuthenticationWithNewUserFails() {
    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => 'abcd',
      'eppn' => 'jdoe@example.com',
      'mail' => 'jdoe@mail.example.com',
    ]);

    $this->container->set('request_stack', $request_stack);

    $handler = $this->container->get('basicshib.authentication_handler');

    try {
      $handler->authenticate();
      $this->fail('Authenticate succeeded, but was not expected to');
    }
    catch (AuthenticationException $exception) {
      self::assertEquals(AuthenticationException::USER_CREATION_NOT_ALLOWED, $exception->getCode());
    }
  }

  public function testAuthenticationWithNewUserSucceeds() {
    /** @var ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');

    $config = $config_factory->getEditable('basicshib.auth_filter');
    $config->set('create', ['allow' => true, 'error' => '']);
    $config->save();

    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => 'abcd',
      'eppn' => 'jdoe@example.com',
      'mail' => 'jdoe@mail.example.com',
    ]);

    $this->container->set('request_stack', $request_stack);

    $handler = $this->container->get('basicshib.authentication_handler');

    try {
      $handler->authenticate();
    }
    catch (AuthenticationException $exception) {
      $this->fail($exception->getTraceAsString());
    }
  }

  public function testAuthenticationWithMissingSessionId() {
    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => '',
      'eppn' => 'jdoe@example.com',
      'mail' => 'jdoe@mail.example.com',
    ]);

    $this->container->set('request_stack', $request_stack);

    $handler = $this->container->get('basicshib.authentication_handler');

    try {
      $handler->authenticate();
      $this->fail('Authenticate succeeded, but was not expected to');
    }
    catch (AuthenticationException $exception) {
      self::assertEquals(AuthenticationException::MISSING_ATTRIBUTES, $exception->getCode());
    }
  }

  public function testAuthenticationWithMissingName() {
    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => 'abcd',
      'eppn' => '',
      'mail' => 'jdoe@mail.example.com',
    ]);

    $this->container->set('request_stack', $request_stack);

    $handler = $this->container->get('basicshib.authentication_handler');

    try {
      $handler->authenticate();
      $this->fail('Authenticate succeeded, but was not expected to');
    }
    catch (AuthenticationException $exception) {
      self::assertEquals(AuthenticationException::MISSING_ATTRIBUTES, $exception->getCode());
    }
  }

  public function testAuthenticationWithMissingMail() {
    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => 'abcd',
      'eppn' => 'jdoe@example.com',
      'mail' => '',
    ]);

    $this->container->set('request_stack', $request_stack);

    $handler = $this->container->get('basicshib.authentication_handler');

    try {
      $handler->authenticate();
      $this->fail('Authenticate succeeded, but was not expected to');
    }
    catch (AuthenticationException $exception) {
      self::assertEquals(AuthenticationException::MISSING_ATTRIBUTES, $exception->getCode());
    }
  }

  public function testAuthenticationWithUserBlocked() {
    $user = $this->createUser('jdoe@example.com', 'jdoe@mail.example.com', 0);

    $request_stack = $this->getMockRequestStack([
      'Shib_Session_ID' => 'abcd',
      'eppn' => $user->getAccountName(),
      'mail' => $user->getEmail(),
    ]);

    $this->container->set('request_stack', $request_stack);

    $handler = $this->container->get('basicshib.authentication_handler');

    try {
      $handler->authenticate();
      $this->fail('An exception was expected');
    }
    catch (\Exception $exception) {
      self::assertEquals(AuthenticationException::USER_BLOCKED, $exception->getCode());
    }
  }

  /**
   * Test clearing session when user is anonymous.
   */
  public function testClearSessionWithAnonymousUser() {
    $request = new Request();
    $request->setSession(new Session());
    $request->getSession()
      ->set(SessionTracker::VARNAME, '1234');

    $request_stack = new RequestStack();
    $request_stack->push($request);

    $this->container->set('request_stack', $request_stack);
    /** @var AuthenticationHandlerInterface $handler */
    $handler = $this->container->get('basicshib.authentication_handler');

    /** @var AccountProxyInterface $account */
    $account = $this->container->get('current_user');

    self::assertTrue($request->getSession()->has(SessionTracker::VARNAME));

    $result = $handler->checkUserSession($request, $account);

    self::assertFalse($request->getSession()->has(SessionTracker::VARNAME));
    self::assertEquals(AuthenticationHandlerInterface::AUTHCHECK_LOCAL_SESSION_EXPIRED, $result);
  }

  /**
   * Test clearing session when user is anonymous.
   */
  public function testClearSessionWithAuthUserAndNoSessionVar() {
    $request = new Request();
    $request->setSession(new Session());
    $request->getSession()
      ->set(SessionTracker::VARNAME, '1234');

    $request_stack = new RequestStack();
    $request_stack->push($request);

    $this->container->set('request_stack', $request_stack);
    /** @var AuthenticationHandlerInterface $handler */
    $handler = $this->container->get('basicshib.authentication_handler');

    /** @var UserInterface $account */
    $account = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create(['name' => 'test', 'mail' => 'test', 'status' => 1]);
    $account->save();

    user_login_finalize($account);

    $proxy = new AccountProxy();
    $proxy->setAccount($account);

    self::assertTrue($account->isAuthenticated());
    self::assertTrue($request->getSession()->has(SessionTracker::VARNAME));

    $result = $handler->checkUserSession($request, $proxy);

    self::assertFalse($request->getSession()->has(SessionTracker::VARNAME));
    self::assertTrue(AuthenticationHandlerInterface::AUTHCHECK_SHIB_SESSION_EXPIRED, $result);
  }

  /**
   * Test clearing session when user is anonymous.
   */
  public function testClearSessionWithAuthUserAndMismatchedSessionVar() {
    $request = new Request();
    $request->setSession(new Session());
    $request->getSession()
      ->set(SessionTracker::VARNAME, '1234');

    $request->server->set('Shib_Session_ID', '4321');

    $request_stack = new RequestStack();
    $request_stack->push($request);

    $this->container->set('request_stack', $request_stack);
    /** @var AuthenticationHandlerInterface $handler */
    $handler = $this->container->get('basicshib.authentication_handler');

    /** @var UserInterface $account */
    $account = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create(['name' => 'test', 'mail' => 'test', 'status' => 1]);
    $account->save();

    user_login_finalize($account);

    $proxy = new AccountProxy();
    $proxy->setAccount($account);

    self::assertTrue($account->isAuthenticated());
    self::assertTrue($request->getSession()->has(SessionTracker::VARNAME));

    $result = $handler->checkUserSession($request, $proxy);

    self::assertFalse($request->getSession()->has(SessionTracker::VARNAME));
    self::assertTrue(AuthenticationHandlerInterface::AUTHCHECK_SHIB_SESSION_ID_MISMATCH, $result);
  }

  /**
   * Test clearing session when user is anonymous.
   */
  public function testClearSessionWithAuthUserAndSameSessionIdIsIgnored() {
    $request = new Request();
    $request->setSession(new Session());
    $request->getSession()
      ->set(SessionTracker::VARNAME, '1234');

    $request->server->set('Shib_Session_ID', '1234');

    $request_stack = new RequestStack();
    $request_stack->push($request);

    $this->container->set('request_stack', $request_stack);
    /** @var AuthenticationHandlerInterface $handler */
    $handler = $this->container->get('basicshib.authentication_handler');

    /** @var UserInterface $account */
    $account = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create(['name' => 'test', 'mail' => 'test', 'status' => 1]);
    $account->save();

    user_login_finalize($account);

    $proxy = new AccountProxy();
    $proxy->setAccount($account);

    self::assertTrue($account->isAuthenticated());
    self::assertTrue($request->getSession()->has(SessionTracker::VARNAME));

    $result = $handler->checkUserSession($request, $proxy);

    self::assertTrue($request->getSession()->has(SessionTracker::VARNAME));
    self::assertEquals(AuthenticationHandlerInterface::AUTHCHECK_IGNORE, $result);
  }

  public function testGetLoginUrl() {
    $url = $this->getMockBuilder(Url::class)
      ->disableOriginalConstructor()
      ->setMethods(['setAbsolute', 'toString'])
      ->getMock();
    $url->method('toString')
      ->willReturn('https://example.com/foo');

    $path_validator = $this->getMockForAbstractClass(PathValidatorInterface::class);
    $path_validator->method('getUrlIfValid')
      ->willReturn($url);
    $this->container->set('path.validator', $path_validator);

    /** @var AuthenticationHandlerInterface $handler */
    $handler = $this->container->get('basicshib.authentication_handler');

    $this->assertEquals('/Shibboleth.sso/Login?target=' . urlencode('https://example.com/foo'), $handler->getLoginUrl());
  }
}
