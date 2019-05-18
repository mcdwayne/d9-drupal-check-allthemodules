<?php

namespace Drupal\Tests\mass_contact\Kernel;

use Drupal\simpletest\UserCreationTrait;

/**
 * Access control handler tests.
 *
 * @group mass_contact
 *
 * @coversDefaultClass \Drupal\mass_contact\CategoryAccessControlHandler
 */
class CategoryAccessControlHandlerTest extends MassContactTestBase {

  use UserCreationTrait;
  use CategoryCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);

    // Create user 1 to avoid super-user permission issues.
    $this->createUser();
  }

  /**
   * Tests view access.
   *
   * @covers ::checkAccess
   */
  public function testViewAccess() {
    // No access.
    $account = $this->createUser();
    $category = $this->createCategory();
    $this->assertFalse($category->access('view', $account));
    $this->assertFalse($category->access('update', $account));
    $this->assertFalse($category->access('create', $account));
    $this->assertFalse($category->access('delete', $account));

    // Admin access.
    $account = $this->createUser(['mass contact administer']);
    $this->assertTrue($category->access('view', $account));
    $this->assertTrue($category->access('update', $account));
    $this->assertTrue($category->access('create', $account));
    $this->assertTrue($category->access('delete', $account));

    // Category access.
    $account = $this->createUser([('mass contact send to users in the ' . $category->id() . ' category')]);
    $this->assertTrue($category->access('view', $account));
    $this->assertFalse($category->access('update', $account));
    $this->assertFalse($category->access('create', $account));
    $this->assertFalse($category->access('delete', $account));

    // Access to a different category should deny access.
    $category2 = $this->createCategory();
    $this->assertFalse($category2->access('view', $account));
    $this->assertFalse($category2->access('update', $account));
    $this->assertFalse($category2->access('create', $account));
    $this->assertFalse($category2->access('delete', $account));
  }

}
