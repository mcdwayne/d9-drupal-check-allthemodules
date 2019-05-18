<?php

namespace Drupal\abstractpermissions;

class PermissionGraph {

  /**
   * The permission governors, keyed by permission ID.
   *
   * @var \Drupal\abstractpermissions\PermissionGovernor[]
   */
  private $governors = [];

    /**
   * PermissionGraph constructor.
   *
   * @param \Drupal\abstractpermissions\Entity\PermissionAbstractionInterface[] $permissionAbstractions
   */
  public function __construct($permissionAbstractions) {
    foreach ($permissionAbstractions as $permissionAbstraction) {
      foreach ($permissionAbstraction->getGovernedPermissions() as $governedPermissionId) {
        if (!isset($this->governors[$governedPermissionId])) {
          $this->governors[$governedPermissionId] = new PermissionGovernor($governedPermissionId, $this);
        }
        $this->governors[$governedPermissionId]->addPermissionAbstraction($permissionAbstraction);
      }
    }
  }

  /**
   * @return \Drupal\abstractpermissions\PermissionGovernor
   */
  public function getGovernor($permissionId) {
    return isset($this->governors[$permissionId]) ? $this->governors[$permissionId] : NULL;
  }

}
