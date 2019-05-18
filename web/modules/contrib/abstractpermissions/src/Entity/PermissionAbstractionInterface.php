<?php

namespace Drupal\abstractpermissions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface PermissionAbstractionInterface extends ConfigEntityInterface {

  /**
   * @return string
   */
  public function getId();

  /**
   * @param string $id
   */
  public function setId($id);

  /**
   * @return string
   */
  public function getLabel();

  /**
   * @param string $label
   */
  public function setLabel($label);

  /**
   * @return string
   */
  public function getDescription();

  /**
   * @param string $description
   */
  public function setDescription($description);

  /**
   * Get abstracted permissions labels keyed by internal ID.
   *
   * @return string[]
   */
  public function getAbstractedPermissions();

  /**
   * Get abstracted permissions info arrays keyed by prefixed permission ID.
   *
   * @return array[]
   */
  public function getAbstractedPermissionsPublicInfo();

  /**
   * Get abstract permission labels, keyed by permission ID.
   *
   * @param string[] $abstractedPermissions
   */
  public function setAbstractedPermissions($abstractedPermissions);

  /**
   * @return string[]
   */
  public function getGovernedPermissions();

  /**
   * @param $governedPermissions
   */
  public function setGovernedPermissions($governedPermissions);

  /**
   * Get permission IDs, keyed by abstracted permission, then numeric index.
   *
   * @return string[][]
   */
  public function getPermissionMapping();

  /**
   * @param string[][] $permissionMapping
   */
  public function setPermissionMapping($permissionMapping);

  /**
   * Get governing factor.
   *
   * @param string $abstractedPermissionId
   * @param string $governedPermissionId
   * @return bool
   */
  public function getGoverningFactor($abstractedPermissionId, $governedPermissionId);

}