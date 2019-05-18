<?php

namespace Drupal\entity_tools;

/**
 * Class UserQuery.
 *
 * Syntactic sugar over the core EntityQuery for filter, sort and limit.
 *
 * @package Drupal\entity_tools
 */
class UserQuery extends AbstractEntityQuery implements EntityQueryInterface {

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct(EntityTools::ENTITY_USER);
    $this->setActive();
    // @todo review this, a query on User should probably not
    // be for the anonymous role
    $this->setAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    // @todo review setAuthenticated()
    $this->coreEntityQuery->condition('roles', $type);
  }

  /**
   * An alias to setType.
   *
   * @param string $role
   *   Role id.
   */
  public function setRole($role) {
    $this->setType($role);
  }

  /**
   * {@inheritdoc}
   */
  public function setTypes(array $types) {
    $group = $this->coreEntityQuery->orConditionGroup();
    foreach ($types as $type) {
      if (is_string($type)) {
        $group->condition('roles', $type);
      }
    }
    $this->coreEntityQuery->condition($group);
  }

  /**
   * An alias to setTypes.
   *
   * @param array $roles
   *   Role ids.
   */
  public function setRoles(array $roles) {
    $this->setTypes($roles);
  }

  /**
   * Filters by authenticated.
   */
  public function setAuthenticated() {
    $this->coreEntityQuery->condition('uid', 0, '>');
  }

  /**
   * Filters by active.
   */
  public function setActive() {
    $this->coreEntityQuery->condition('status', 1);
  }

  /**
   * Filters by blocked.
   */
  public function setBlocked() {
    $this->coreEntityQuery->condition('status', 0);
  }

}
