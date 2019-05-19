<?php

namespace Drupal\Tests\changelog\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group changelog
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['changelog'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'access changelog',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('/changelog'));
    $this->assertResponse(200);
    $this->drupalGet(Url::fromRoute('/admin/config/changelog'));

  }

}
