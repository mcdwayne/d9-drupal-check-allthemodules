<?php

namespace Drupal\Tests\cognito\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the logging in using the email flow.
 *
 * @group cognito
 */
class EmailFlowLoginTest extends BrowserTestBase {

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
   * Test the successful login flow.
   */
  public function testCanLoginWithCognitoAccount() {
    $this->drupalPostForm('/user/login', [
      'mail' => $this->mail,
      'pass' => 'letmein',
    ], 'Log in');

    // Logged in.
    $this->assertSession()->addressEquals('/user/2');
    $this->assertSession()->statusCodeEquals(200);
  }

}
