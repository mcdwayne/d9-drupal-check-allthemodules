<?php

namespace Drupal\Tests\cognito\Kernel\Email;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Aws\CognitoResult;
use Drupal\cognito\Form\Email\NewPasswordForm;
use Drupal\cognito\Plugin\cognito\CognitoFlowInterface;
use Drupal\cognito_tests\NullCognito;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test for cognito new password form.
 *
 * @group cognito
 */
class NewPasswordFormTest extends KernelTestBase {

  /**
   * Test when we don't need to set our new password.
   */
  public function testSetNewPasswordNotRequired() {
    $formObj = new NewPasswordForm(new NullCognito());
    $form = [];
    $formState = new FormState();
    $formObj->validateForgotPassword($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('You do not need to update your password.', array_pop($errors));
  }

  /**
   * Attempt to set a new password but fail the challenge.
   */
  public function testSetNewPasswordChallengeFailed() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('authorize')
      ->willReturn(new CognitoResult([
        'ChallengeName' => CognitoFlowInterface::NEW_PASSWORD_REQUIRED,
        'Session' => '123-456-89',
      ], NULL, TRUE));
    $cognito
      ->method('adminRespondToNewPasswordChallenge')
      ->willReturn(new CognitoResult([], new \Exception('Challenge failed')));

    $formObj = new NewPasswordForm($cognito);
    $form = [];
    $formState = new FormState();
    $formObj->validateForgotPassword($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Challenge failed', array_pop($errors));
  }

  /**
   * Set new password success.
   */
  public function testSetNewPasswordSuccess() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('authorize')
      ->willReturn(new CognitoResult([
        'ChallengeName' => CognitoFlowInterface::NEW_PASSWORD_REQUIRED,
        'Session' => '123-456-89',
      ], NULL, TRUE));
    $cognito
      ->method('adminRespondToNewPasswordChallenge')
      ->willReturn(new CognitoResult([
        'AuthenticationResult' => [
          'AccessToken' => 'accessToken',
        ],
      ]));
    $cognito
      ->method('changePassword')
      ->with('accessToken', 'tempPass', 'newPass')
      ->willReturn(new CognitoResult([]));

    $formObj = new NewPasswordForm($cognito);
    $form = [];
    $formState = new FormState();
    $formState->setValue('temporary_password', 'tempPass');
    $formState->setValue('new_password', 'newPass');
    $formObj->validateForgotPassword($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(0, $errors);
  }

}
