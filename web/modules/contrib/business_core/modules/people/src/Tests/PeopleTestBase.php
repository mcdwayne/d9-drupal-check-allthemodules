<?php

namespace Drupal\people\Tests;

use Drupal\cbo_organization\Tests\OrganizationTestBase;
use Drupal\people\Entity\People;

/**
 * Provides helper functions for people module tests.
 */
abstract class PeopleTestBase extends OrganizationTestBase {

  use PeopleTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['people'];

  /**
   * A user with employee role.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $employeeUser;

  /**
   * A people.
   *
   * @var \Drupal\people\PeopleInterface
   */
  protected $people;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->people = $this->createPeople();

    $this->employeeUser = $this->drupalCreateUser();
    $this->employeeUser->addRole('employee');
    $this->employeeUser->people->target_id = $this->people->id();
    $this->employeeUser->save();

  }

}
