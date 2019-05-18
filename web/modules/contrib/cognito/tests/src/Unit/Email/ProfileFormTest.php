<?php

namespace Drupal\Tests\cognito\Unit\Email;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Aws\CognitoResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Tests\cognito\Traits\RegisterFormHelper;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * Test the profile form.
 *
 * @group cognito
 */
class ProfileFormTest extends UnitTestCase {

  use RegisterFormHelper;

  /**
   * Email updates require current password.
   */
  public function testCannotUpdateEmailWithoutCurrentPassword() {
    $cognito = $this->createMock(CognitoInterface::class);
    $user = $this->createMock(UserInterface::class);
    $user->method('getEmail')->willReturn('ben-old@example.com');

    $mockCurrentUser = $this->createMock(UserInterface::class);
    $mockCurrentUser->method('hasPermission')->willReturn(FALSE);

    // Set a fake user into the container to bypass the hasPermission calls for
    // the admin users.
    $container = new ContainerBuilder();
    $container->set('current_user', $mockCurrentUser);
    \Drupal::setContainer($container);

    $formState = new FormState();
    $formState->setValues([
      'pass' => 'pass',
      'mail' => 'ben-new@example.com',
    ]);
    $form = [];
    $formObj = $this->getProfileForm($cognito);
    $formObj->setEntity($user);
    $formObj->validateEmailChange($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('You must provide your existing password to update your email', array_pop($errors));
  }

  /**
   * If the user tries to change their password without their current password.
   */
  public function testWarnUserWhenUpdatingPasswordWithoutCurrentPassword() {
    $cognito = $this->createMock(CognitoInterface::class);

    $formState = new FormState();
    $formState->setValues([
      'pass' => 'newpass',
      'mail' => 'ben@example.com',
    ]);
    $form = [];
    $formObj = $this->getProfileForm($cognito);
    $formObj->validatePasswordChange($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('You must provide your existing password to set a new password', array_pop($errors));
  }

  /**
   * Test successfully updating a password.
   */
  public function testValidateFormPasswordUpdated() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->expects($this->once())
      ->method('authorize')
      ->with('ben@example.com', 'oldpass')
      ->willReturn(new CognitoResult([
        'AuthenticationResult' => [
          'AccessToken' => '123,',
        ],
      ]));
    $cognito->method('changePassword')->willReturn(new CognitoResult([]));

    $formState = new FormState();
    $formState->setValues([
      'current_pass' => 'oldpass',
      'pass' => 'newpass',
      'mail' => 'ben@example.com',
    ]);
    $form = [];
    $formObj = $this->getProfileForm($cognito);
    $formObj->validatePasswordChange($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(0, $errors);
  }

  /**
   * Test no changes made when password is empty.
   */
  public function testNoPasswordTriggersNoChanges() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->expects($this->never())
      ->method('authorize');

    $formState = new FormState();
    $form = [];
    $formObj = $this->getProfileForm($cognito);
    $formObj->validatePasswordChange($form, $formState);
  }

  /**
   * Test changing a password with failed authorization.
   */
  public function testPasswordChangeAuthorizeError() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->expects($this->once())
      ->method('authorize')
      ->willReturn(new CognitoResult([], new \Exception('Failed to authenticate')));

    $formState = new FormState();
    $formState->setValues([
      'current_pass' => 'oldpass',
      'pass' => 'newpass',
      'mail' => 'ben@example.com',
    ]);
    $form = [];
    $formObj = $this->getProfileForm($cognito);
    $formObj->validatePasswordChange($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Failed to authenticate', array_pop($errors));
  }

}
