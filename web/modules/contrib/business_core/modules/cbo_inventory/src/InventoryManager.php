<?php

namespace Drupal\cbo_inventory;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\people\PeopleManagerInterface;

/**
 * Inventory manager contains common functions to manage inventory.
 */
class InventoryManager implements InventoryManagerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The people manager service.
   *
   * @var \Drupal\people\PeopleManagerInterface
   */
  protected $peopleManager;

  /**
   * Construct the SubinventoryManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\people\PeopleManagerInterface $people_manager
   *   The people manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PeopleManagerInterface $people_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->peopleManager = $people_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function currentInventoryOrganization() {
    if ($organization = $this->peopleManager->currentOrganization()) {
      if ($organization->get('inventory_organization')->value) {
        return $organization;
      }
      else {
        $parents = $this->entityTypeManager->getStorage('organization')
          ->loadParents($organization->id());
        foreach ($parents as $parent) {
          if ($parent->get('inventory_organization')->value) {
            return $parent;
          }
        }
      }
    }
  }

}
