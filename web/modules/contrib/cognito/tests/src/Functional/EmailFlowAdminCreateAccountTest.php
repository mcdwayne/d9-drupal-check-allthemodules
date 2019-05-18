<?php

namespace Drupal\Tests\cognito\Functional;

/**
 * Test admins creating new accounts.
 *
 * @group cognito
 */
class EmailFlowAdminCreateAccountTest extends CognitoTestBase {

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin = $this->createExternalUser([
      'administer permissions',
      'administer users',
    ]);

    $this->drupalPostForm('/user/login', [
      'mail' => $this->admin->getEmail(),
      'pass' => 'letmein',
    ], 'Log in');
  }

  /**
   * Create an account as an admin.
   */
  public function testAdminCanRegisterAccounts() {
    $this->drupalGet('/admin/people/create');
    $this->assertSession()->fieldNotExists('pass[pass1]');

    $mail = strtolower($this->randomMachineName() . '@example.com');
    $this->drupalPostForm('/admin/people/create', [
      'mail' => $mail,
      'status' => 1,
    ], 'Register');

    $this->assertSession()->pageTextContains('The account has been created and the user has been sent a temporary password to login');

    $this->assertSession()->addressEquals('/admin/people/create');
    $this->assertSession()->statusCodeEquals(200);

    $accounts = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $mail]);
    $this->assertCount(1, $accounts);
    $account = array_pop($accounts);
    $this->assertEquals($mail, $account->getEmail());

    // Ensure the user exists in the authmap.
    $this->assertEquals($mail, \Drupal::service('externalauth.authmap')->get($account->id(), 'cognito'));
  }

}
