<?php

namespace Drupal\Tests\mass_contact\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\mass_contact\Kernel\CategoryCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Base class for functional Mass Contact tests.
 */
abstract class MassContactTestBase extends BrowserTestBase {

  use CategoryCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'mass_contact',
    'user',
  ];

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * Some roles to test with.
   *
   * @var \Drupal\user\RoleInterface[]
   */
  protected $roles;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Dummy user 1.
    $this->createUser();

    $this->admin = $this->createUser([
      'mass contact administer',
      'access administration pages',
    ]);

    $this->drupalPlaceBlock('local_actions_block');

    foreach (range(1, 5) as $i) {
      $rid = $this->createRole([]);
      $this->roles[$i] = Role::load($rid);
    }
  }

}
