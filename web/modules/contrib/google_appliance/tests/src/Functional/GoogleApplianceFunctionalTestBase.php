<?php

namespace Drupal\Tests\google_appliance\Functional;

use Drupal\simpletest\BlockCreationTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Defines a base class for Google Appliance tests.
 */
abstract class GoogleApplianceFunctionalTestBase extends BrowserTestBase {

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
    'google_appliance',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->createUser([
      'administer google appliance',
      'access google appliance content',
    ]);
    // Let anonymous users access search results.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->grantPermission('access google appliance content')->save();
  }

}
