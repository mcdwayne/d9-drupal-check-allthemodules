<?php

namespace Drupal\Tests\mass_contact\Kernel\Entity;

use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\mass_contact\Kernel\CategoryCreationTrait;
use Drupal\Tests\mass_contact\Kernel\MassContactTestBase;
use Drupal\user\Entity\Role;

/**
 * Kernel tests for the mass contact category entity.
 *
 * @group mass_contact
 *
 * @coversDefaultClass \Drupal\mass_contact\Entity\MassContactCategory
 */
class MassContactCategoryTest extends MassContactTestBase {

  use CategoryCreationTrait;
  use UserCreationTrait;

  /**
   * Roles to test with.
   *
   * @var \Drupal\user\RoleInterface[]
   */
  protected $roles;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    foreach (['foo', 'bar', 'baz'] as $rid) {
      $this->roles[$rid] = Role::load($this->createRole([], $rid, ucfirst($rid)));
    }
  }

  /**
   * Test CRUD operations.
   *
   * @covers ::setRecipients
   * @covers ::getRecipients
   * @covers ::getGroupingCategories
   */
  public function testCrud() {
    $category = $this->createCategory();
    $grouping = $category->getGroupingCategories('role');
    $this->assertFalse($grouping);

    $recipients = [
      'role' => [
        'categories' => [
          'foo',
          'bar',
        ],
        'conjunction' => 'AND',
      ],
    ];
    $category->setRecipients($recipients);
    $this->assertEquals($recipients, $category->getRecipients());

    $this->assertFalse($category->getGroupingCategories('foo'));
    $grouping = $category->getGroupingCategories('role');
    $this->assertEquals(['foo', 'bar'], $grouping->getCategories());
    $this->assertEquals('Roles: <em class="placeholder">Foo, Bar</em>', $grouping->displayCategories(['foo', 'bar']));
  }

}
