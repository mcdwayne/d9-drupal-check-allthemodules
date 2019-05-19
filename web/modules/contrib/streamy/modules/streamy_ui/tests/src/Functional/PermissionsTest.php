<?php

namespace Drupal\Tests\streamy_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the permissions provided by this module.
 *
 * @group streamy_ui
 */
class PermissionsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['streamy_ui'];

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

  }

  /**
   * Test administer streamy permission.
   */
  public function testUserWithoutAndWithRightPermissionsOnStreamyForm() {
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/media/file-system/streamy');
    $this->assertSession()->statusCodeEquals(403);

    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer streamy']);
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/media/file-system/streamy');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test administer streamy local permission.
   */
  public function testUserWithoutAndWithRightPermissionsOnLocalForm() {
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/media/file-system/streamy/streams/local');
    $this->assertSession()->statusCodeEquals(403);

    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer streamy local']);
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/media/file-system/streamy/streams/local');
    $this->assertSession()->statusCodeEquals(200);
  }
}

