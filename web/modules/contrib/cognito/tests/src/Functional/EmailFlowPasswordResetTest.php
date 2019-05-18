<?php

namespace Drupal\Tests\cognito\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test password reset using the email flow.
 *
 * @group cognito
 */
class EmailFlowPasswordResetTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'cognito',
    'cognito_tests',
  ];

  /**
   * Test email.
   *
   * @var string
   */
  protected $mail;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->mail = strtolower($this->randomMachineName() . '@example.com');

    // Register a sample account that we can login with.
    \Drupal::service('externalauth.externalauth')
      ->register($this->mail, 'cognito', [
        'name' => $this->mail,
      ]);
  }

  /**
   * Test the successful password reset flow.
   */
  public function testCanResetPassword() {
    // Reset the users password.
    $mail = strtolower($this->randomMachineName() . '@example.com');
    $this->drupalPostForm('/user/password', [
      'mail' => $mail,
    ], 'Reset Password');

    // Confirm your account.
    $this->drupalPostForm(NULL, ['confirmation_code' => '12345'], 'Confirm');
    $this->assertSession()->pageTextContains('Your password has now been reset.');
    $this->assertSession()->addressEquals('/user/login');
  }

}
