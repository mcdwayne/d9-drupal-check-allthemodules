<?php

namespace Drupal\Tests\decoupled_auth\Functional;

use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\decoupled_auth\Tests\DecoupledAuthUserCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user password reset form for decoupled users.
 *
 * @group decoupled_auth
 *
 * @covers \Drupal\decoupled_auth\Form\UserPasswordFormAlter
 */
class UserPasswordFormTest extends BrowserTestBase {

  use DecoupledAuthUserCreationTrait;
  use AssertMailTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'decoupled_auth',
  ];

  /**
   * Run the tests for core.
   *
   * @see \Drupal\Tests\decoupled_auth\Functional\UserPasswordFormTest::doTest
   *
   * @dataProvider dataCore
   */
  public function testCore(array $users, $email, $expected_message, $expected_user_key = FALSE) {
    $this->doTest($users, $email, $expected_message, $expected_user_key);
  }

  /**
   * Data provider for ::testCore.
   *
   * @return array
   *   The test data.
   */
  public function dataCore() {
    $data = [];

    $data['no-user'] = [
      'users' => [],
      'email' => 'test@example.com',
      'expected_message' => 'Error message test@example.com is not recognized as a username or an email address.',
      'expected_user_key' => FALSE,
    ];

    $data['only-decoupled'] = [
      'users' => [
        0 => ['decoupled' => TRUE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Error message test@example.com is not recognized as a username or an email address.',
      'expected_user_key' => FALSE,
    ];

    $data['only-coupled'] = [
      'users' => [
        0 => ['decoupled' => FALSE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Status message Further instructions have been sent to your email address.',
      'expected_user_key' => 0,
    ];

    $data['only-coupled-blocked'] = [
      'users' => [
        0 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => ['status' => 0],
        ],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Error message test@example.com is blocked or has not been activated yet.',
      'expected_user_key' => FALSE,
    ];

    $data['decoupled-coupled'] = [
      'users' => [
        0 => ['decoupled' => TRUE, 'email_prefix' => 'test'],
        1 => ['decoupled' => FALSE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Status message Further instructions have been sent to your email address.',
      'expected_user_key' => 1,
    ];

    $data['decoupled-coupled-blocked'] = [
      'users' => [
        0 => ['decoupled' => TRUE, 'email_prefix' => 'test'],
        1 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => ['status' => 0],
        ],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Error message test@example.com is blocked or has not been activated yet.',
      'expected_user_key' => FALSE,
    ];

    $data['coupled-decoupled'] = [
      'users' => [
        0 => ['decoupled' => FALSE, 'email_prefix' => 'test'],
        1 => ['decoupled' => TRUE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Status message Further instructions have been sent to your email address.',
      'expected_user_key' => 0,
    ];

    $data['coupled-blocked-decoupled'] = [
      'users' => [
        0 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => ['status' => 0],
        ],
        1 => ['decoupled' => TRUE, 'email_prefix' => 'test'],
      ],
      'email' => 'test@example.com',
      'expected_message' => 'Error message test@example.com is blocked or has not been activated yet.',
      'expected_user_key' => FALSE,
    ];

    return $data;
  }

  /**
   * Run the tests for core.
   *
   * @see \Drupal\Tests\decoupled_auth\Functional\UserPasswordFormTest::doTest
   *
   * @dataProvider dataUserRegistrationPassword
   */
  public function testUserRegistrationPassword(array $users, $email, $expected_message, $expected_user_key = FALSE, $expected_url = 'user/reset') {
    try {
      $success = $this->container->get('module_installer')->install(['user_registrationpassword'], TRUE);
      $this->assertTrue($success, 'Enabled user_registrationpassword');
    }
    catch (MissingDependencyException $e) {
      // The exception message has all the details.
      $this->fail($e->getMessage());
    }

    $this->rebuildContainer();

    $this->doTest($users, $email, $expected_message, $expected_user_key, $expected_url);
  }

  /**
   * Data provider for ::testUserRegistrationPassword.
   *
   * @return array
   *   The test data.
   */
  public function dataUserRegistrationPassword() {
    $data = $this->dataCore();

    $data['only-coupled-blocked-accessed'] = $data['only-coupled-blocked'];
    $data['only-coupled-blocked-accessed']['users'][0]['values']['login'] = 1;

    $data['only-coupled-blocked-logged-in'] = $data['only-coupled-blocked'];
    $data['only-coupled-blocked-logged-in']['users'][0]['values']['access'] = 1;

    $data['only-coupled-blocked']['expected_message'] = 'Status message Further instructions have been sent to your email address.';
    $data['only-coupled-blocked']['expected_user_key'] = 0;
    $data['only-coupled-blocked']['expected_url'] = 'user/registrationpassword';

    $data['decoupled-coupled-blocked-accessed'] = $data['decoupled-coupled-blocked'];
    $data['decoupled-coupled-blocked-accessed']['users'][1]['values']['login'] = 1;

    $data['decoupled-coupled-blocked-logged-in'] = $data['decoupled-coupled-blocked'];
    $data['decoupled-coupled-blocked-logged-in']['users'][1]['values']['access'] = 1;

    $data['decoupled-coupled-blocked']['expected_message'] = 'Status message Further instructions have been sent to your email address.';
    $data['decoupled-coupled-blocked']['expected_user_key'] = 1;
    $data['decoupled-coupled-blocked']['expected_url'] = 'user/registrationpassword';

    $data['coupled-blocked-logged-in-decoupled'] = $data['coupled-blocked-decoupled'];
    $data['coupled-blocked-logged-in-decoupled']['users'][0]['values']['login'] = 1;

    $data['coupled-blocked-access-decoupled'] = $data['coupled-blocked-decoupled'];
    $data['coupled-blocked-access-decoupled']['users'][0]['values']['access'] = 1;

    $data['coupled-blocked-decoupled']['expected_message'] = 'Status message Further instructions have been sent to your email address.';
    $data['coupled-blocked-decoupled']['expected_user_key'] = 0;
    $data['coupled-blocked-decoupled']['expected_url'] = 'user/registrationpassword';

    return $data;
  }

  /**
   * Run a password reset test scenario.
   *
   * @param array $users
   *   An array of users to create. Each user is an array of:
   *   - decoupled: Whether the user should be decoupled.
   *   - email_prefix: The email prefix to use, which will also be the name if
   *     coupled.
   *   - values: Optionally an array of other values to set on the user.
   * @param string $email
   *   The email address to enter on the form.
   * @param string $expected_message
   *   The expecte message on the form.
   * @param bool $expected_user_key
   *   If we are expecting a user match, the key from $users we expect to match.
   * @param string $expected_url
   *   The expected URL for the link in the email, if sent.
   */
  protected function doTest(array $users, $email, $expected_message, $expected_user_key = FALSE, $expected_url = 'user/reset') {
    // Create our users, tracking our expected user.
    $expected_user = FALSE;
    foreach ($users as $key => $values) {
      $values += ['values' => []];
      $user = $this->createUnsavedUser($values['decoupled'], $values['email_prefix'], $values['values']);
      $user->save();
      if ($key === $expected_user_key) {
        $expected_user = $user->id();
      }
    }

    // Check we have an expected user, if expected.
    if ($expected_user_key !== FALSE) {
      $this->assertNotEmpty($expected_user, 'Found expected user');
    }

    // Submit password reset form.
    $this->drupalGet('user/password');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $page = $this->getSession()->getPage();
    $input = $page->findField('name');
    $input->setValue($email);
    $page->pressButton('Submit');

    // Check our resulting page.
    $page = $this->getSession()->getPage();
    $message = $page->find('css', '.messages');
    $this->assertNotEmpty($message, 'Message found');
    $this->assertTrue($message->hasClass($expected_user_key === FALSE ? 'messages--error' : 'messages--status'), 'Message is of correct type');
    $this->assertSame($expected_message, $message->getText(), 'Message has correct text');

    // If we have an expected user, check our email sent correctly.
    if ($expected_user) {
      $this->assertMail('to', $email, 'Password email sent to user');
      $this->assertMailString('body', "/{$expected_url}/{$expected_user}", 1, 'Correct user in reset email');
    }
    // Otherwise there should be no email.
    else {
      $this->assertEmpty($this->container->get('state')->get('system.test_mail_collector'), 'No emails sent');
    }
  }

}
