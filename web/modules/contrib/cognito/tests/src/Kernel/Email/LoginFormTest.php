<?php

namespace Drupal\Tests\cognito\Kernel\Email;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\CommandInterface;
use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Aws\CognitoResult;
use Drupal\cognito\Form\Email\UserLoginForm;
use Drupal\Core\Form\FormState;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\cognito\Unit\CognitoMessagesStub;
use Drupal\user\Entity\User;

/**
 * Kernel test for cognito login form.
 *
 * @group cognito
 */
class LoginFormTest extends KernelTestBase {

  public static $modules = [
    'system',
    'cognito',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Test a successful login.
   */
  public function testUserCanLogin() {
    $externalauth = $this->createMock(ExternalAuthInterface::class);
    $cognitoFlowManager = $this->container->get('plugin.manager.cognito.cognito_flow');
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('authorize')
      ->willReturn(new CognitoResult([]));

    $formObj = new UserLoginForm($cognito, new CognitoMessagesStub(), $cognitoFlowManager, $externalauth);

    $user = User::create([
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $user->save();

    $form = [];
    $formState = new FormState();
    $formState->setValue('mail', $user->getUsername());
    $formObj->validateAuthentication($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(0, $errors);
  }

  /**
   * Ensure we cannot login as a blocked user.
   */
  public function testCannotLoginBlockedUser() {
    $externalauth = $this->createMock(ExternalAuthInterface::class);
    $cognitoFlowManager = $this->container->get('plugin.manager.cognito.cognito_flow');
    $cognito = $this->createMock(CognitoInterface::class);
    $formObj = new UserLoginForm($cognito, new CognitoMessagesStub(), $cognitoFlowManager, $externalauth);

    $user = User::create([
      'name' => $this->randomMachineName(),
      'status' => 0,
    ]);
    $user->save();

    $form = [];
    $formState = new FormState();
    $formState->setValue('mail', $user->getUsername());
    $formObj->validateAuthentication($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Your account is blocked', array_pop($errors));
  }

  /**
   * Test Cognito exceptions are handled.
   */
  public function testLoginCognitoException() {
    $externalauth = $this->createMock(ExternalAuthInterface::class);
    $cognitoFlowManager = $this->container->get('plugin.manager.cognito.cognito_flow');
    $cognito = $this->createMock(CognitoInterface::class);
    $command = $this->createMock(CommandInterface::class);

    $exception = new CognitoIdentityProviderException('Exception message', $command, [
      'message' => 'Unable to authenticate user because service is down.',
    ]);

    $cognito
      ->method('authorize')
      ->willReturn(new CognitoResult(NULL, $exception));

    $formObj = new UserLoginForm($cognito, new CognitoMessagesStub(), $cognitoFlowManager, $externalauth);

    $form = [];
    $formState = new FormState();
    $formObj->validateAuthentication($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Unable to authenticate user because service is down.', array_pop($errors));
  }

  /**
   * Ensure non-cognito exceptions are handled.
   */
  public function testLoginNonCognitoException() {
    $externalauth = $this->createMock(ExternalAuthInterface::class);
    $cognitoFlowManager = $this->container->get('plugin.manager.cognito.cognito_flow');
    $cognito = $this->createMock(CognitoInterface::class);
    $exception = new \Exception('Request failed');

    $cognito
      ->method('authorize')
      ->willReturn(new CognitoResult(NULL, $exception));

    $formObj = new UserLoginForm($cognito, new CognitoMessagesStub(), $cognitoFlowManager, $externalauth);

    $form = [];
    $formState = new FormState();
    $formObj->validateAuthentication($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Request failed', array_pop($errors));
  }

  /**
   * Test the login challenge.
   */
  public function testLoginChallenge() {
    $externalauth = $this->createMock(ExternalAuthInterface::class);
    $cognitoFlowManager = $this->container->get('plugin.manager.cognito.cognito_flow');

    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('authorize')
      ->willReturn(new CognitoResult(['ChallengeName' => 'NEW_PASSWORD_REQUIRED'], NULL, TRUE));

    $formObj = new UserLoginForm($cognito, new CognitoMessagesStub(), $cognitoFlowManager, $externalauth);

    $form = [];
    $formState = new FormState();
    $formObj->validateAuthentication($form, $formState);

    $this->assertEquals('cognito.challenge.new_password', $formState->getRedirect()->getRouteName());
  }

}
