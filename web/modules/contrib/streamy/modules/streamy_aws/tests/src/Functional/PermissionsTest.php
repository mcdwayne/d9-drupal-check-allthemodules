<?php

namespace Drupal\Tests\streamy_aws\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the permissions provided by this module.
 *
 * @group streamy_aws
 */
class PermissionsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['streamy_aws'];

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
   * Test administer streamy aws permission.
   */
  public function testUserWithoutAndWithRightPermissionsOnDropboxForm() {
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/media/file-system/streamy/cdn/awscdn');
    $this->assertSession()->statusCodeEquals(403);

    $this->user = $this->drupalCreateUser(['administer site configuration', 'administer streamy aws']);
    $this->drupalLogin($this->user);

    $this->drupalGet('/admin/config/media/file-system/streamy/cdn/awscdn');
    $this->assertSession()->statusCodeEquals(200);
  }

}

