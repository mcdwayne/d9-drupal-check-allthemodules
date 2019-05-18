<?php

namespace Drupal\abstractpermissions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;

class AbstractPermissionsService implements AbstractPermissionsServiceInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * @var \Drupal\abstractpermissions\Entity\PermissionAbstractionInterface[]
   */
  protected $permissionAbstractions;

  /**
   * @var \Drupal\abstractpermissions\PermissionGraph
   */
  protected $permissionGraph;

  /**
   * AbstractPermissionsService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\user\PermissionHandlerInterface $permissionHandler
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, PermissionHandlerInterface $permissionHandler) {
    $this->entityTypeManager = $entityTypeManager;
    $this->permissionHandler = $permissionHandler;
  }


  /**
   * {@inheritDoc}
   */
  public function getPermissionAbstractions() {
    if (!isset($this->permissionAbstractions)) {
      $this->permissionAbstractions = $this->entityTypeManager
        ->getStorage('abstractpermissions_abstraction')->loadByProperties();
    }
    return $this->permissionAbstractions;
  }

  /**
   * {@inheritDoc}
   */
  public function getPermissionGraph() {
    if (!isset($this->permissionGraph)) {
      $this->permissionGraph = new PermissionGraph($this->getPermissionAbstractions());
    }
    return $this->permissionGraph;
  }

  public function invalidate() {
    unset($this->permissionAbstractions);
    unset($this->permissionGraph);
  }

  public function permissionCallback() {
    $permissionsInfo = [];
    foreach ($this->getPermissionAbstractions() as $permissionAbstraction) {
      $permissionsInfo += $permissionAbstraction->getAbstractedPermissionsPublicInfo();
    }
    return $permissionsInfo;
  }

  /**
   * Set all governed permissions to their should-be value.
   *
   * @param \Drupal\user\RoleInterface $role
   */
  public function denormalizeRole(RoleInterface $role) {
    $permissionGraph = $this->getPermissionGraph();
    foreach ($this->permissionHandler->getPermissions() as $permissionName => $permissionInfo) {
      $governor = $permissionGraph->getGovernor($permissionName);
      if (isset($governor)) {
        $permissionValue = $governor->getPermissionValue($role);
        $this->setRolePermission($role, $permissionName, $permissionValue);
      }
    }
  }

  protected function getRolePermission(RoleInterface $role, $permission) {
    // Just to make it explicit.
    if ($permission === FALSE) {
      return FALSE;
    }
    return in_array($permission, $role->getPermissions());
  }

  protected function setRolePermission(RoleInterface $role, $permission, $value) {
    $oldValue = $this->getRolePermission($role, $permission);
    if ($value && !$oldValue) {
      $role->grantPermission($permission);
    }
    elseif (!$value && $oldValue) {
      $role->revokePermission($permission);
    }
  }

}
