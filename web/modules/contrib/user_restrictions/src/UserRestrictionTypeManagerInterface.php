<?php

namespace Drupal\user_restrictions;

/**
 * Defines the interface for the UserRestrictionTypeManager.
 */
interface UserRestrictionTypeManagerInterface {

  /**
   * Get a list of all registered plugin instances.
   *
   * @return \Drupal\user_restrictions\Plugin\UserRestrictionTypeInterface[]
   *   List of UserRestrictionType plugin instances.
   */
  public function getTypes();

  /**
   * Get a single plugin instance.
   *
   * @param string $id
   *   The plugin ID.
   *
   * @return \Drupal\user_restrictions\Plugin\UserRestrictionTypeInterface
   *   The UserRestrictionType plugin instance.
   */
  public function getType($id);

  /**
   * Get a list of all plugins names for option lists.
   *
   * @return array
   *   List of plugin names keyed by plugin ID.
   */
  public function getTypesAsOptions();

}
