<?php

namespace Drupal\Tests\radioactivity\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests Radioactivity Rest functionality.
 *
 * @group radioactivity
 */
class RestTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['radioactivity'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $this->grantPermissions($role, ['access content']);
  }

  /**
   * Tests that the REST endpoint does not accept invalid requests.
   */
  public function testRestValidation() {
    $this->drupalGet('radioactivity/emit');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('"status":"error"');
    $this->assertSession()->responseContains('"message":"Empty request."');
  }

}
