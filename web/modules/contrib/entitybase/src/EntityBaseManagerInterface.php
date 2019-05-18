<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseManagerInterface.
 */

namespace Drupal\entity_base;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interface defining an entity manager.
 */
interface EntityBaseManagerInterface {

  /**
   * Instantiates a new instance of this entity handler.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   *
   * @return static
   *   A new instance of the entity handler.
   */
  public static function create(ContainerInterface $container);

  /**
   * Returns the list of all entities.
   *
   * @return array
   */
  public function getAll();

  /**
   * Returns the list of available entities for specific user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *  User object
   *
   * @return array
   */
  public function getAvailable(AccountInterface $user);

  /**
   * Returns the set of entities by field value.
   *
   * @param $field_name
   * @param $field_value
   * @return array|bool
   */
  public function getByField($field_name, $field_value);
}
