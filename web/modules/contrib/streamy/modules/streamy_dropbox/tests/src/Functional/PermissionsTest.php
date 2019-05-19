<?php

namespace Drupal\Tests\streamy_dropbox\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the permissions provided by this module.
 *
 * @group streamy_dropbox
 */
class PermissionsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['streamy_dropbox'];

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
   * Test administer streamy dropbox permission.
   */
  public function testUserWithoutAndWithRightPermissionsOnDropboxForm() {
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/media/file-system/streamy/streams/dropbox');
    $this->assertSession()->statusCodeEquals(403);

    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer streamy dropbox']);
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/media/file-system/streamy/streams/dropbox');
    $this->assertSession()->statusCodeEquals(200);
  }

}

