<?php

namespace Drupal\cbo_organization;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for organizations.
 */
class OrganizationStorage extends SqlContentEntityStorage implements OrganizationStorageInterface {

  /**
   * Array of loaded parents keyed by child organization ID.
   *
   * @var array
   */
  protected $parents = [];

  /**
   * {@inheritdoc}
   */
  public function loadAllChildren($oid) {
    $children = $this->loadByProperties(['parent' => $oid]);
    foreach ($children as $entity) {
      $children += $this->loadAllChildren($entity->id());
    }

    return $children;
  }

  /**
   * {@inheritdoc}
   */
  public function loadParents($oid) {
    if (!isset($this->parents[$oid])) {
      $parents = [];
      if ($entity = $this->load($oid)) {
        $parents[$entity->id()] = $entity;

        while ($entity = $entity->getParent()) {
          $parents[$entity->id()] = $entity;
        }
      }

      $this->parents[$oid] = $parents;
    }
    return $this->parents[$oid];
  }

}
