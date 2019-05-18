<?php

namespace Drupal\Tests\cognito\Unit\Email;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\Aws\CognitoResult;
use Drupal\Core\Form\FormState;
use Drupal\Tests\cognito\Traits\RegisterFormHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Test the admin register form.
 *
 * @group cognito
 */
class AdminRegisterFormTest extends UnitTestCase {

  use RegisterFormHelper;

  /**
   * Test the validate registration error.
   */
  public function testValidateRegistrationError() {
    $email = 'test@example.com';
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('adminSignup')
      ->with($email, $email)
      ->willReturn(new CognitoResult([], new \Exception('Validation failed')));

    $formState = new FormState();
    $formState->setValue('mail', $email);
    $form = [];
    $formObj = $this->getAdminRegisterForm($cognito);

    $formObj->validateRegistration($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals('Validation failed', array_pop($errors));
  }

  /**
   * Test the validate registration error.
   */
  public function testValidateRegistrationSuccess() {
    $cognito = $this->createMock(CognitoInterface::class);
    $cognito
      ->method('adminSignup')
      ->willReturn(new CognitoResult([]));

    $formState = new FormState();
    $form = [];
    $formObj = $this->getAdminRegisterForm($cognito);

    $formObj->validateRegistration($form, $formState);

    $errors = $formState->getErrors();
    $this->assertCount(0, $errors);
  }

}
