<?php

namespace Drupal\entity_access_audit\Dimensions;

use Drupal\entity_access_audit\AccessDimensionInterface;
use Drupal\user\RoleInterface;

/**
 * Dimension for user roles.
 */
class RoleDimension implements AccessDimensionInterface {

  /**
   * The user role.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $userRole;

  /**
   * RoleDimension constructor.
   */
  public function __construct(RoleInterface $role) {
    $this->userRole = $role;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('User role');
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensionValue() {
    return $this->userRole->label();
  }

  /**
   * Get the role ID.
   *
   * @return string
   *   The role ID.
   */
  public function getRoleId() {
    return $this->userRole->id();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->userRole->id();
  }

}
