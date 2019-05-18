<?php

namespace Drupal\Tests\decoupled_auth\Functional;

use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\decoupled_auth\Tests\DecoupledAuthUserCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user password login form for decoupled users.
 *
 * @group decoupled_auth
 *
 * @covers \Drupal\decoupled_auth\Form\UserLoginFormAlter
 */
class UserLoginFormTest extends BrowserTestBase {

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
  public function testCore(array $users, array $form_values, $expected_message, $expected_user_key = FALSE) {
    $this->doTest($users, $form_values, $expected_message, $expected_user_key);
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
      'values' => ['name' => 'test', 'pass' => 'test'],
      'expected_message' => 'Error message Unrecognized username or password. Forgot your password?',
      'expected_user_key' => FALSE,
    ];

    $data['only-decoupled'] = [
      'users' => [
        0 => [
          'decoupled' => TRUE,
          'email_prefix' => 'test',
          'values' => ['password' => 'testing'],
        ],
      ],
      'values' => ['name' => 'test', 'pass' => 'testing'],
      'expected_message' => 'Error message Unrecognized username or password. Forgot your password?',
      'expected_user_key' => FALSE,
    ];

    $data['only-coupled'] = [
      'users' => [
        0 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => ['pass' => 'testing'],
        ],
      ],
      'values' => ['name' => 'test', 'pass' => 'testing'],
      'expected_message' => FALSE,
      'expected_user_key' => 0,
    ];

    $data['only-coupled-blocked'] = [
      'users' => [
        0 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => [
            'pass' => 'testing',
            'status' => 0,
          ],
        ],
      ],
      'values' => ['name' => 'test', 'pass' => 'testing'],
      'expected_message' => 'Error message The username test has not been activated or is blocked.',
      'expected_user_key' => FALSE,
    ];

    $data['decoupled-coupled'] = [
      'users' => [
        0 => [
          'decoupled' => TRUE,
          'email_prefix' => 'test',
          'values' => ['password' => 'testing'],
        ],
        1 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => ['pass' => 'testing'],
        ],
      ],
      'values' => ['name' => 'test', 'pass' => 'testing'],
      'expected_message' => FALSE,
      'expected_user_key' => 1,
    ];

    $data['decoupled-coupled-blocked'] = [
      'users' => [
        0 => [
          'decoupled' => TRUE,
          'email_prefix' => 'test',
          'values' => ['password' => 'testing'],
        ],
        1 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => [
            'pass' => 'testing',
            'status' => 0,
          ],
        ],
      ],
      'values' => ['name' => 'test', 'pass' => 'testing'],
      'expected_message' => 'Error message The username test has not been activated or is blocked.',
      'expected_user_key' => FALSE,
    ];

    $data['coupled-decoupled'] = [
      'users' => [
        0 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => ['pass' => 'testing'],
        ],
        1 => [
          'decoupled' => TRUE,
          'email_prefix' => 'test',
          'values' => ['password' => 'testing'],
        ],
      ],
      'values' => ['name' => 'test', 'pass' => 'testing'],
      'expected_message' => FALSE,
      'expected_user_key' => 0,
    ];

    $data['coupled-blocked-decoupled'] = [
      'users' => [
        0 => [
          'decoupled' => FALSE,
          'email_prefix' => 'test',
          'values' => [
            'pass' => 'testing',
            'status' => 0,
          ],
        ],
        1 => [
          'decoupled' => TRUE,
          'email_prefix' => 'test',
          'values' => ['password' => 'testing'],
        ],
      ],
      'values' => ['name' => 'test', 'pass' => 'testing'],
      'expected_message' => 'Error message The username test has not been activated or is blocked.',
      'expected_user_key' => FALSE,
    ];

    return $data;
  }

  /**
   * Run the tests for core.
   *
   * @see \Drupal\Tests\decoupled_auth\Functional\UserPasswordFormTest::doTest
   *
   * @dataProvider dataEmailRegistration
   */
  public function testEmailRegistration(array $users, $email, $expected_message, $expected_user_key = FALSE, $login_with_username = FALSE) {
    try {
      $success = $this->container->get('module_installer')->install(['email_registration'], TRUE);
      $this->assertTrue($success, 'Enabled email_registration');
    }
    catch (MissingDependencyException $e) {
      // The exception message has all the details.
      $this->fail($e->getMessage());
    }

    $this->rebuildContainer();

    $this->config('email_registration.settings')
      ->set('login_with_username', $login_with_username)
      ->save();

    $this->doTest($users, $email, $expected_message, $expected_user_key);
  }

  /**
   * Data provider for ::testEmailRegistration.
   *
   * @return array
   *   The test data.
   */
  public function dataEmailRegistration() {
    $data = [];

    foreach ($this->dataCore() as $key => $test_data) {
      if ($test_data['expected_message'] == 'Error message Unrecognized username or password. Forgot your password?') {
        $test_data['expected_message'] = 'Error message Unrecognized e-mail address or password. Forgot your password?';
      }

      $adjusted_data = $test_data;
      $adjusted_data['expected_message'] = 'Error message Unrecognized e-mail address or password. Forgot your password?';
      $adjusted_data['expected_user_key'] = FALSE;
      $data['email-only-name-' . $key] = $adjusted_data;

      $adjusted_data = $test_data;
      $adjusted_data['values']['name'] .= '@example.com';
      $data['email-only-email-' . $key] = $adjusted_data;

      $adjusted_data = $test_data;
      $adjusted_data['login_with_username'] = TRUE;
      $data['both-name-' . $key] = $adjusted_data;

      $adjusted_data = $test_data;
      $adjusted_data['values']['name'] .= '@example.com';
      $adjusted_data['login_with_username'] = TRUE;
      $data['both-email-' . $key] = $adjusted_data;
    }

    return $data;
  }

  /**
   * Run a login test scenario.
   *
   * @param array $users
   *   An array of users to create. Each user is an array of:
   *   - decoupled: Whether the user should be decoupled.
   *   - email_prefix: The email prefix to use, which will also be the name if
   *     coupled.
   *   - values: Optionally an array of other values to set on the user.
   * @param array $form_values
   *   The values for the login form.
   * @param string $expected_message
   *   The expected message on the form.
   * @param bool $expected_user_key
   *   If we are expecting a user match, the key from $users we expect to match.
   */
  protected function doTest(array $users, array $form_values, $expected_message, $expected_user_key = FALSE) {
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

    // Submit login form.
    $this->drupalPostForm('user/login', $form_values, 'Log in');

    // Check for a message.
    if ($expected_message) {
      $page = $this->getSession()->getPage();
      $message = $page->find('css', '.messages');
      $this->assertNotEmpty($message, 'Message found');
      $this->assertTrue($message->hasClass($expected_user_key === FALSE ? 'messages--error' : 'messages--status'), 'Message is of correct type');
      $this->assertSame($expected_message, $message->getText(), 'Message has correct text');
    }

    // Check if we've been logged in correctly.
    if ($expected_user) {
      $this->assertSession()->addressEquals('user/' . $expected_user);
    }
  }

}
