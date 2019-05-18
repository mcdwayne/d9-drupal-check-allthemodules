<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/28/17
 * Time: 12:45 PM
 */

namespace Drupal\Tests\basicshib\Kernel;


use Drupal\basicshib\AuthenticationHandlerInterface;
use Drupal\basicshib\Controller\LoginController;
use Drupal\basicshib\Exception\AuthenticationException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\basicshib\Traits\MockTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginControllerTest extends KernelTestBase {
  use MockTrait;

  public static $modules = ['basicshib', 'system', 'user'];

  /**
   * @var AuthenticationHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  private $auth_handler;

  /**
   * @var array
   */
  private $messages;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['basicshib']);

    $this->auth_handler = $this->getMockForAbstractClass(
      AuthenticationHandlerInterface::class
    );

    $this->container->set('basicshib.authentication_handler', $this->auth_handler);

    $this->messages = $this->container->get('config.factory')
      ->get('basicshib.settings')
      ->get('messages');
  }

  /**
   * Login with default configuration.
   */
  public function testLoginWithDefaultConfig() {
    $controller = LoginController::create($this->container);
    $response = $controller->login();

    self::assertEquals(RedirectResponse::class, get_class($response));
    self::assertEquals('/user', $response->getTargetUrl());
  }

  /**
   * Login with internal redirect specified via URL
   */
  public function testLoginWithInternalRedirect() {
    /** @var RequestStack $request_stack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->getCurrentRequest()->query
      ->set('after_login', '/admin');

    $controller = LoginController::create($this->container);
    $response = $controller->login();

    self::assertEquals(RedirectResponse::class, get_class($response));
    self::assertEquals('/admin', $response->getTargetUrl());
  }

  /**
   * Login with external redirect specified.
   */
  public function testLoginWithExternalRedirect() {

    /** @var RequestStack $request_stack */
    $request_stack = $this->container->get('request_stack');
    $request_stack->getCurrentRequest()->query
      ->set('after_login', 'https://example.com');

    $controller = LoginController::create($this->container);
    $response = $controller->login();


    self::assertTrue(is_array($response));
    self::assertContains($this->messages['external_redirect_error'], $this->render($response));
  }

  /**
   * Login when user is blocked.
   */
  public function testLoginWithUserBlocked() {
    $this->auth_handler->method('authenticate')
      ->willThrowException(
        new AuthenticationException('', AuthenticationException::USER_BLOCKED
      ));

    $controller = LoginController::create($this->container);
    $response = $controller->login();

    self::assertTrue(is_array($response));
    self::assertContains($this->messages['account_blocked_error'], $this->render($response));
  }

  /**
   * Login when user login is prevented.
   */
  public function testLoginWithUserLoginDisabled() {
    $this->auth_handler->method('authenticate')
      ->willThrowException(
        new AuthenticationException('', AuthenticationException::LOGIN_DISALLOWED_FOR_USER
      ));

    $controller = LoginController::create($this->container);
    $response = $controller->login();

    self::assertTrue(is_array($response));
    self::assertContains($this->messages['login_disallowed_error'], $this->render($response));
  }


  /**
   * Login when user login is prevented.
   */
  public function testLoginWithUserCreationNotAllowed() {
    $this->auth_handler->method('authenticate')
      ->willThrowException(
        new AuthenticationException('', AuthenticationException::USER_CREATION_NOT_ALLOWED
        ));

    $controller = LoginController::create($this->container);
    $response = $controller->login();

    self::assertTrue(is_array($response));
    self::assertContains($this->messages['user_creation_not_allowed_error'], $this->render($response));
  }

  /**
   * Login when user login is prevented.
   */
  public function testUnclassifiedLoginError() {
    $this->auth_handler->method('authenticate')
      ->willThrowException(
        new AuthenticationException('', AuthenticationException::UNCLASSIFIED_ERROR
        ));

    $controller = LoginController::create($this->container);
    $response = $controller->login();

    self::assertTrue(is_array($response));
    self::assertContains($this->messages['generic_login_error'], $this->render($response));
  }

}
