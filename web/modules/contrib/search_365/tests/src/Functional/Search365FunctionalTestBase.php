<?php

namespace Drupal\Tests\search_365\Functional;

use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Defines a base class for Google Appliance tests.
 */
abstract class Search365FunctionalTestBase extends BrowserTestBase {

  use UserCreationTrait;
  use BlockCreationTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'search_365',
    'search_365_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->createUser([
      'administer search 365',
      'access search 365 content',
    ]);
    // Let anonymous users access search results.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->grantPermission('access search 365 content');
    $role->save();

    $this->placeBlock('page_title_block');
  }

}
