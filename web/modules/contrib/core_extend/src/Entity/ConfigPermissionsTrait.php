<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides a trait for a configuration entity with permissions capabilities.
 */
trait ConfigPermissionsTrait {

  /**
   * The permissions belonging to this config entity.
   *
   * @var array
   */
  protected $permissions = [];

  /**
   * An indicator whether the config entity's permissions are absolute.
   *
   * @var bool
   */
  protected $is_absolute = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    if ($this->isAbsolute()) {
      return [];
    }
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    if ($this->isAbsolute()) {
      return TRUE;
    }
    return in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermissions(array $permissions) {
    $this->permissions = array_unique(array_merge($this->permissions, $permissions));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermission($permission) {
    if ($this->isAbsolute()) {
      return $this;
    }
    if (!$this->hasPermission($permission)) {
      $this->permissions[] = $permission;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermissions(array $permissions) {
    $this->permissions = array_diff($this->permissions, $permissions);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermission($permission) {
    if ($this->isAbsolute()) {
      return $this;
    }
    $this->permissions = array_diff($this->permissions, [$permission]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function changePermissions(array $permissions = []) {
    // Grant new permissions to the config entity.
    $grant = array_filter($permissions);
    if (!empty($grant)) {
      $this->grantPermissions(array_keys($grant));
    }

    // Revoke permissions from the config entity.
    $revoke = array_diff_assoc($permissions, $grant);
    if (!empty($revoke)) {
      $this->revokePermissions(array_keys($revoke));
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAbsolute() {
    return (bool) $this->is_absolute;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsAbsolute($is_absolute) {
    $this->is_absolute = $is_absolute;
    return $this;
  }

}
