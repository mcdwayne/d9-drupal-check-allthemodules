<?php

namespace Drupal\Tests\one_time_password\Functional;

use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Test the form that helps users setup their one time password.
 *
 * @coversDefaultClass \Drupal\one_time_password\Form\PasswordSetupForm
 * @group one_time_password
 */
class PasswordSetupFormTest extends BrowserTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'one_time_password',
    'user',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test the password setup form.
   */
  public function testForm() {
    $test_user = $this->createUser();

    // Ensure the access control is working for the TFA route.
    $this->drupalGet('/user/' . $test_user->id() . '/two-factor-auth');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure the user has no one time password.
    $user = $this->reloadUser($test_user->id());
    $this->assertTrue($user->one_time_password->isEmpty());

    // Login as the user and enable TFA.
    $this->drupalLogin($test_user);
    $this->drupalGet($test_user->toUrl());
    $this->clickLink('Two Factor Authentication');
    $this->submitForm([], 'Enable Two Factor Authentication');
    $this->assertSession()->pageTextContains('Two factor authentication has been enabled. See the instructions below for setting up your one time password.');

    // Ensure TFA has been correctly setup.
    $user = $this->reloadUser($test_user->id());
    $this->assertFalse($user->one_time_password->isEmpty());

    // Disable TFA and ensure it is removed from the user.
    $this->submitForm([], 'Disable Two Factor Authentication');
    $this->assertSession()->pageTextContains('Two factor authentication has been disabled for this account.');
    $user = $this->reloadUser($test_user->id());
    $this->assertTrue($user->one_time_password->isEmpty());
  }

  /**
   * Reload a user entity.
   */
  protected function reloadUser($id) {
    $this->container->get('entity_type.manager')->getStorage('user')->resetCache();
    return User::load($id);
  }

}
