<?php

namespace Drupal\Tests\cognito\Unit\Email;

use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Aws\CommandInterface;
use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Aws\CognitoResult;
use Drupal\cognito\Form\Email\PassResetForm;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for cognito password reset form.
 *
 * @group cognito
 */
class PasswordResetFormTest extends UnitTestCase {

  /**
   * Test validate forgot password exceptions.
   */
  public function testForgotPasswordCognitoException() {
    $cognito = $this->createMock(CognitoInterface::class);
    $command = $this->createMock(CommandInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $exception = new CognitoIdentityProviderException('Exception message', $command, [
      'message' => 'Unable to reset password at this time.',
    ]);

    $cognito
      ->method('forgotPassword')
      ->willReturn(new CognitoResult(NULL, $exception));

    $formObj = new PassResetForm($cognito, $logger);

    $form = [];
    $formState = new FormState();
    $formObj->validateForgotPassword($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Unable to reset password at this time.', array_pop($errors));
  }

  /**
   * Test validate forgot password exceptions.
   */
  public function testForgotPasswordNonCognitoException() {
    $cognito = $this->createMock(CognitoInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $exception = new \Exception('Password reset failed');

    $cognito
      ->method('forgotPassword')
      ->willReturn(new CognitoResult(NULL, $exception));

    $formObj = new PassResetForm($cognito, $logger);

    $form = [];
    $formState = new FormState();
    $formObj->validateForgotPassword($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Password reset failed', array_pop($errors));
  }

  /**
   * Test we can successfully confirm our forgotten password.
   */
  public function testConfirmationSuccessful() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('confirmForgotPassword')
      ->willReturn(new CognitoResult(NULL));

    $logger = $this->createMock(LoggerInterface::class);
    $logger
      ->expects($this->once())
      ->method('notice')
      ->with('Password reset for %email.');

    $formObj = new PassResetForm($cognito, $logger);
    $property = new \ReflectionProperty($formObj, 'multistepFormValues');
    $property->setAccessible(TRUE);
    $property->setValue($formObj, ['mail' => 'test@example.com']);

    $form = [];
    $formState = new FormState();
    $formObj->validateConfirmation($form, $formState);
  }

  /**
   * Test confirm forgot password exceptions.
   */
  public function testConfirmForgotPasswordCognitoException() {
    $cognito = $this->createMock(CognitoInterface::class);
    $command = $this->createMock(CommandInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $exception = new CognitoIdentityProviderException('Exception message', $command, [
      'message' => 'Confirmation code is incorrect.',
    ]);

    $cognito
      ->method('confirmForgotPassword')
      ->willReturn(new CognitoResult(NULL, $exception));

    $formObj = new PassResetForm($cognito, $logger);
    $property = new \ReflectionProperty($formObj, 'multistepFormValues');
    $property->setAccessible(TRUE);
    $property->setValue($formObj, ['mail' => 'test@example.com']);

    $form = [];
    $formState = new FormState();
    $formObj->validateConfirmation($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Confirmation code is incorrect.', array_pop($errors));
  }

  /**
   * Test confirm forgot password exceptions.
   */
  public function testConfirmForgotPasswordNonCognitoException() {
    $cognito = $this->createMock(CognitoInterface::class);
    $exception = new \Exception('Something went wrong');
    $logger = $this->createMock(LoggerInterface::class);

    $cognito
      ->method('confirmForgotPassword')
      ->willReturn(new CognitoResult(NULL, $exception));

    $formObj = new PassResetForm($cognito, $logger);
    $property = new \ReflectionProperty($formObj, 'multistepFormValues');
    $property->setAccessible(TRUE);
    $property->setValue($formObj, ['mail' => 'test@example.com']);

    $form = [];
    $formState = new FormState();
    $formObj->validateConfirmation($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Something went wrong', array_pop($errors));
  }

}
