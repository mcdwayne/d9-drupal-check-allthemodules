<?php

namespace Drupal\Tests\cognito\Unit\Email;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\CommandInterface;
use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Aws\CognitoResult;
use Drupal\Core\Form\FormState;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\Tests\cognito\Traits\RegisterFormHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Test the registration form.
 *
 * @group cognito
 */
class RegisterFormTest extends UnitTestCase {

  use RegisterFormHelper;

  /**
   * Test a failed validation.
   */
  public function testValidateRegistrationError() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('signUp')
      ->willReturn(new CognitoResult([], new \Exception('Registration failed')));

    $formState = new FormState();
    $form = [];
    $formObj = $this->getRegisterForm($cognito);

    $formObj->validateRegistration($form, $formState);

    $errors = $formState->getErrors();
    $this->assertEquals('Registration failed', array_pop($errors));
  }

  /**
   * When we fail with a UsernameExistsException we attempt to resend.
   */
  public function testValidateRegistrationAttemptResendThatThrowsError() {
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
      ->willReturn(new CognitoResult([], new \Exception('Failed to resend confirmation')));

    $formState = new FormState();
    $formState->clearErrors();
    $formState->setValue('mail', $email);
    $form = [];
    $formObj = $this->getRegisterForm($cognito);

    $formObj->validateRegistration($form, $formState);

    $errors = $formState->getErrors();
    $this->assertStringStartsWith('You already have an account.', (string) array_pop($errors));
  }

  /**
   * Test a successful validation of the confirmation form.
   */
  public function testValidateConfirmation() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('confirmSignup')
      ->willReturn(new CognitoResult([]));

    $formState = new FormState();
    $form = [];
    $formObj = $this->getRegisterForm($cognito);
    $property = new \ReflectionProperty($formObj, 'multistepFormValues');
    $property->setAccessible(TRUE);
    $property->setValue($formObj, ['mail' => 'test@example.com']);

    $formObj->validateConfirmation($form, $formState);

    $this->assertCount(0, $formState->getErrors());
  }

  /**
   * Test a failed validation of the confirmation form.
   */
  public function testValidateConfirmationFailed() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('confirmSignup')
      ->willReturn(new CognitoResult([], new \Exception('Confirmation failed')));

    $formState = new FormState();
    $form = [];
    $formObj = $this->getRegisterForm($cognito);
    $property = new \ReflectionProperty($formObj, 'multistepFormValues');
    $property->setAccessible(TRUE);
    $property->setValue($formObj, ['mail' => 'test@example.com']);

    $formObj->validateConfirmation($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Confirmation failed', array_pop($errors));
  }

  /**
   * Tests the registration form works when autoConfirm is enabled.
   */
  public function testAutoConfirmRegistration() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('signUp')
      ->willReturn(new CognitoResult([]));

    $externalAuth = $this->createMock(ExternalAuthInterface::class);
    $externalAuth
      ->expects($this->once())
      ->method('login');

    $formObj = $this->getRegisterForm($cognito, $externalAuth);

    $email = 'test@example.com';
    $formState = new FormState();
    $formState->clearErrors();
    $formState->setValue('mail', $email);
    $form = [];

    $configFactory = $this->getConfigFactoryStub([
      'cognito.settings' => ['click_to_confirm_enabled' => 0, 'auto_confirm_enabled' => 1],
    ]);
    $formObj->setConfigFactory($configFactory);
    $formObj->submitForm($form, $formState);

    $this->assertCount(0, $formState->getErrors());
  }

}
