<?php

namespace Drupal\abstractpermissions;

use Drupal\abstractpermissions\Entity\PermissionAbstractionInterface;
use Drupal\user\RoleInterface;

class PermissionGovernor {

  /**
   * The permission ID.
   *
   * @var string
   */
  private $permissionId;

  /**
   * The permission graph.
   *
   * @var \Drupal\abstractpermissions\PermissionGraph
   */
  private $graph;

  /**
   * @var PermissionAbstractionInterface[]
   */
  private $permissionAbstractions = [];

  /**
   * The effective factor of each permission, keyed by its ID.
   *
   * @var bool[]
   */
  private $directFactors = [];

  /**
   * The effective factor of each permission, keyed by its ID.
   *
   * @var bool[]
   */
  private $effectiveFactors;

  /**
   * The circular references.
   *
   * @var string[][]
   */
  private $circles;

  /**
   * PermissionGovernor constructor.
   *
   * @param string $permissionId
   * @param \Drupal\abstractpermissions\PermissionGraph $graph
   */
  public function __construct($permissionId, PermissionGraph $graph) {
    $this->graph = $graph;
    $this->permissionId = $permissionId;
  }

  /**
   * Add permission abstraction.
   *
   * @param \Drupal\abstractpermissions\Entity\PermissionAbstractionInterface $permissionAbstraction
   */
  public function addPermissionAbstraction(PermissionAbstractionInterface $permissionAbstraction) {
    $this->permissionAbstractions[$permissionAbstraction->id()] = $permissionAbstraction;
    foreach ($permissionAbstraction->getAbstractedPermissionsPublicInfo() as $abstractedPermissionId => $_) {
      $factor = $permissionAbstraction->getGoverningFactor($abstractedPermissionId, $this->permissionId);
      // We might safely ignore null factors for the permission calculation,
      // but keep them for circular reference detection.
      $this->directFactors[$abstractedPermissionId] = $factor;
    }
  }

  /**
   * Get permission abstractions.
   *
   * @return \Drupal\abstractpermissions\Entity\PermissionAbstractionInterface[]
   */
  public function getPermissionAbstractions() {
    return $this->permissionAbstractions;
  }

  /**
   * @return mixed
   */
  public function getEffectiveFactors($trail = []) {
    if (!isset($this->effectiveFactors)) {
      // We found a circular dependency. No governor makes sense in this case.
      if (isset($trail[$this->permissionId])) {
        $this->effectiveFactors = [];
        $this->circles = [$trail];
        return [];
      }
      $newTrail = array_merge($trail, [$this->permissionId => $this->permissionId]);
      $effectiveFactors = [];
      foreach ($this->directFactors as $directPermissionId => $directFactor) {
        if (
          ($nextGovernor = $this->graph->getGovernor($directPermissionId))
          && ($nextFactors = $nextGovernor->getEffectiveFactors($newTrail))
        ) {
          // The direct governing permission is itself governed.
          foreach ($nextFactors as $nextPermissionId => $nextFactor) {
            $effectiveFactors[$nextPermissionId] = $directFactor && $nextFactor;
          }
        }
        else {
          // This is the end, my friend.
          $effectiveFactors[$directPermissionId] = $directFactor;
        }
      }
      $this->effectiveFactors = $effectiveFactors;
      $this->circles = [];
    }
    return $this->effectiveFactors;
  }

  /**
   * @return \string[][]
   */
  public function getCircles() {
    if (!isset($this->circles)) {
      $this->getEffectiveFactors();
    }
    return $this->circles;
  }

  /**
   * Get permission value.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The role.
   * @return bool
   *   The new value.
   */
  public function getPermissionValue(RoleInterface $role) {
    $settingFactors = array_filter($this->getEffectiveFactors());
    $permissions = array_fill_keys($role->getPermissions(), TRUE);
    // Ser permission if a governing permission with nonnull factor is set.
    $permissionValue = (bool)array_intersect_key($settingFactors, $permissions);
    return $permissionValue;
  }

}
