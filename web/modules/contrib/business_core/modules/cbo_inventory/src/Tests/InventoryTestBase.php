<?php

namespace Drupal\cbo_inventory\Tests;

use Drupal\cbo_inventory\Entity\Subinventory;
use Drupal\cbo_item\Tests\ItemTestBase;
use Drupal\cbo_organization\Tests\OrganizationTrait;
use Drupal\people\Tests\PeopleTrait;

/**
 * Provides helper functions for cbo_inventory module tests.
 */
abstract class InventoryTestBase extends ItemTestBase {

  use OrganizationTrait;
  use PeopleTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cbo_inventory'];

  /**
   * An organization.
   *
   * @var \Drupal\cbo_organization\OrganizationInterface
   */
  protected $organization;

  /**
   * An people.
   *
   * @var \Drupal\people\PeopleInterface
   */
  protected $people;

  /**
   * A subinventory.
   *
   * @var \Drupal\cbo_inventory\SubinventoryInterface
   */
  protected $subinventory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->organization = $this->createOrganization([
      'inventory_organization' => 1,
    ]);
    $this->people = $this->createPeople();
    $this->subinventory = $this->createSubinventory();
  }

  /**
   * Creates a subinventory based on default settings.
   */
  protected function createSubinventory(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'type' => 'storage',
      'title' => $this->randomMachineName(8),
      'number' => $this->randomMachineName(8),
      'organization' => $this->organization->id(),
    ];
    $entity = Subinventory::create($settings);
    $entity->save();

    return $entity;
  }

}
