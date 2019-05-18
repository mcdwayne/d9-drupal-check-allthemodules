<?php

namespace Drupal\entity_grants\Access;

/**
 * Defines an interface to list available permissions.
 */
interface GrantPermissionHandlerInterface {

  /**
   * Gets all defined entity permissions.
   *
   * @param $entity_type_id
   *
   * @return array
   */
  public function getPermissions($entity_type_id);

}
