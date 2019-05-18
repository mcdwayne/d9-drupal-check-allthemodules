<?php

namespace Drupal\core_extend\Entity;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides a trait for making a config entity a role with permissions.
 */
trait RoleEntityTrait {

  /**
   * The Role ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Role label.
   *
   * @var string
   */
  protected $label;

  /**
   * The permissions belonging to this role.
   *
   * @var array
   */
  protected $permissions = [];

  /**
   * The weight of this role in administrative listings.
   *
   * @var int
   */
  protected $weight;

  /**
   * An indicator whether the role has all permissions.
   *
   * @var bool
   */
  protected $is_admin = FALSE;

  /**
   * {@inheritdoc}
   */
  public function isAdmin() {
    return (bool) $this->is_admin;
  }

  /**
   * {@inheritdoc}
   */
  public function setIsAdmin($is_admin) {
    $this->is_admin = $is_admin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get("{$this->entityTypeId}.locked");
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    if ($this->isAdmin() || !is_array($this->permissions)) {
      return [];
    }
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    if ($this->isAdmin()) {
      return TRUE;
    }
    return in_array($permission, $this->getPermissions());
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermissions(array $permissions) {
    $this->permissions = array_unique(array_merge($this->getPermissions(), $permissions));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermission($permission) {
    if ($this->isAdmin()) {
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
    $this->permissions = array_diff($this->getPermissions(), $permissions);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermission($permission) {
    if ($this->isAdmin()) {
      return $this;
    }
    $this->permissions = array_diff($this->getPermissions(), [$permission]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function changePermissions(array $permissions = []) {
    // Grant new permissions to the role.
    $grant = array_filter($permissions);
    if (!empty($grant)) {
      $this->grantPermissions(array_keys($grant));
    }

    // Revoke permissions from the role.
    $revoke = array_diff_assoc($permissions, $grant);
    if (!empty($revoke)) {
      $this->revokePermissions(array_keys($revoke));
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // Sort the queried roles by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!isset($this->weight) && ($roles = $storage->loadMultiple())) {
      // Set a role weight to make this new role last.
      $max = array_reduce($roles, function ($max, $role) {
          return $max > $role->weight ? $max : $role->weight;
      });
      $this->weight = $max + 1;
    }

    if (!is_array($this->permissions)) {
      $this->permissions = [];
    }

    if (!$this->isSyncing()) {
      // Permissions are always ordered alphabetically to avoid conflicts in the
      // exported configuration.
      sort($this->permissions);
    }
  }

}
