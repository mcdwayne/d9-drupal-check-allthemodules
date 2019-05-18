<?php

namespace Drupal\Tests\cognito\Kernel\Email;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\CommandInterface;
use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Aws\CognitoResult;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\cognito\Traits\RegisterFormHelper;

/**
 * Test the registration form.
 *
 * @group cognito
 */
class RegisterFormTest extends KernelTestBase {

  use RegisterFormHelper;

  /**
   * We do not attempt to query Cognito if there was a form error previously.
   */
  public function testNoRegistrationAttemptedIfPreviousErrors() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->expects($this->never())
      ->method('signUp');

    $formState = new FormState();
    $formState->setErrorByName('test', 'random error');
    $form = [];
    $formObj = $this->getRegisterForm($cognito);

    $this->assertFalse($formObj->validateRegistration($form, $formState));
  }

  /**
   * When we fail with a UsernameExistsException we attempt to resend.
   */
  public function testValidateRegistrationAttemptResend() {
    $email = 'test@example.com';
    $command = $this->createMock(CommandInterface::class);
    $exception = new CognitoIdentityProviderException('Exception message', $command, [
      'message' => 'Unable to reset password at this time.',
      'code' => 'UsernameExistsException',
    ]);

    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('signUp')
      ->willReturn(new CognitoResult([], $exception));
    $cognito
      ->expects($this->once())
      ->method('resendConfirmationCode')
      ->with($email)
      ->willReturn(new CognitoResult([]));

    $formState = new FormState();
    $formState->setValue('mail', $email);
    $form = [];
    $formObj = $this->getRegisterForm($cognito);

    $formObj->validateRegistration($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(0, $errors);

    // We should be rebuilding the form if we are going to show the next step.
    $this->assertTrue($formState->isRebuilding());
  }

}
