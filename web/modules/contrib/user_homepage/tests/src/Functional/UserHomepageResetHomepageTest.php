<?php

namespace Drupal\Tests\user_homepage\Functional;

use Drupal;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the reset homepage functionality works correctly.
 *
 * @group user_homepage
 */
class UserHomepageResetHomepageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_homepage', 'block'];

  /**
   * A user account with permissions to configure his own homepage.
   *
   * @var \Drupal\user\Entity\User
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create user with permission to set a custom homepage.
    $this->user = $this->drupalCreateUser(['configure own homepage']);

    // Create some pre-configured homepage data for the user.
    // $this->getDatabaseConnection() seems to fail to write the data.
    Drupal::database()
      ->merge('user_homepage')
      ->key('uid', $this->user->id())
      ->fields(['uid' => $this->user->id(), 'path' => '/node'])
      ->execute();

    // Add the 'reset' homepage button to a theme region.
    $this->placeBlock('user_homepage_reset_button');
  }

  /**
   * Tests the user can reset his homepage and is not redirected after login.
   */
  public function testResetHomepage() {
    // Test user is redirected upon login, unset homepage, and check redirect
    // does not happen anymore.
    $this->drupalLogin($this->user);
    $this->assertEquals($this->getUrl(), $this->baseUrl . '/node');

    $this->drupalGet('<front>');
    $this->assertSession()->buttonExists('Unset configured homepage');
    $this->drupalPostForm(NULL, [], 'Unset configured homepage');
    $this->drupalLogout();
    $this->drupalLogin($this->user);
    $this->assertEquals($this->baseUrl . '/' . 'user/' . $this->user->id(), $this->getUrl());
  }

}
