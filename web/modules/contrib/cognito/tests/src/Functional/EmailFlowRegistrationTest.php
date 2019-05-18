<?php

namespace Drupal\Tests\cognito\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test registration using the email flow.
 *
 * @group cognito
 */
class EmailFlowRegistrationTest extends BrowserTestBase {

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
   * Test the successful registration flow.
   */
  public function testCanRegister() {
    // Register a new user.
    $mail = strtolower($this->randomMachineName() . '@example.com');
    $this->drupalPostForm('/user/register', [
      'mail' => $mail,
      'pass[pass1]' => 'letmein',
      'pass[pass2]' => 'letmein',
    ], 'Register');

    // Confirm your account.
    $this->drupalPostForm(NULL, ['confirmation_code' => '12345'], 'Confirm');
    $this->assertSession()->pageTextContains('Your account is now confirmed.');
    $this->assertSession()->addressEquals('/user/2');

    // Ensure the user entity was created.
    $accounts = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $mail]);
    $this->assertCount(1, $accounts);
    /** @var \Drupal\user\UserInterface $account */
    $account = array_pop($accounts);
    $this->assertEquals($mail, $account->getEmail());

    // Ensure the user exists in the authmap.
    $this->assertEquals($mail, \Drupal::service('externalauth.authmap')->get($account->id(), 'cognito'));

    $this->assertFalse($account->isBlocked());
  }

  /**
   * Test registering using the click to confirm method.
   */
  public function testCanRegisterClickConfirmation() {
    $this->container->get('config.factory')
      ->getEditable('cognito.settings')
      ->set('click_to_confirm_enabled', TRUE)->save();

    // Register a new user.
    $mail = strtolower($this->randomMachineName() . '@example.com');
    $this->drupalPostForm('/user/register', [
      'mail' => $mail,
      'pass[pass1]' => 'letmein',
      'pass[pass2]' => 'letmein',
    ], 'Register');

    $this->assertSession()->pageTextContains('Please click the link in your email to confirm your account.');

    // Confirm our account.
    $this->drupalGet(Url::fromRoute('cognito.confirm', [
      'confirmation_code' => '12345',
      'base64_email' => base64_encode($mail),
    ]));
    $this->assertSession()->pageTextContains('Your account is now confirmed.');
    $this->assertSession()->addressEquals('/user/2');

    // Ensure the user entity was created.
    $accounts = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $mail]);
    $this->assertCount(1, $accounts);
    /** @var \Drupal\user\UserInterface $account */
    $account = array_pop($accounts);
    $this->assertEquals($mail, $account->getEmail());

    // Ensure the user exists in the authmap.
    $this->assertEquals($mail, \Drupal::service('externalauth.authmap')->get($account->id(), 'cognito'));

    $this->assertFalse($account->isBlocked());
  }

}
