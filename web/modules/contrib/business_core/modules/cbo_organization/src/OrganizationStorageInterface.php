<?php

namespace Drupal\cbo_organization;

/**
 * Defines an interface for organization entity storage classes.
 */
interface OrganizationStorageInterface {

  /**
   * Finds all children of a given organization ID.
   *
   * @param int $oid
   *   Organization ID to retrieve children for.
   *
   * @return \Drupal\cbo_organization\OrganizationInterface[]
   *   An array of organization objects which are the children of the
   *   organization $oid.
   */
  public function loadAllChildren($oid);

  /**
   * Finds all parents of a given organization ID.
   *
   * @param int $oid
   *   Organization ID to retrieve parents for.
   *
   * @return \Drupal\cbo_organization\OrganizationInterface[]
   *   An array of organization objects which are the parents of the organization $tid.
   */
  public function loadParents($oid);

}
