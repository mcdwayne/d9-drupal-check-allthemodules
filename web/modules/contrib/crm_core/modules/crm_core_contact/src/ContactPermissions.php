<?php

namespace Drupal\crm_core_contact;

use Drupal\crm_core\CRMCorePermissions;

/**
 * Class ContactPermissions.
 */
class ContactPermissions {

  /**
   * Returns Individual and Organization permissions.
   *
   * @return array
   *   CRM Core Individual and Organization permissions.
   */
  public function permissions() {
    $perm_builder = new CRMCorePermissions();

    return array_merge($perm_builder->entityTypePermissions('crm_core_individual'), $perm_builder->entityTypePermissions('crm_core_organization'));
  }

}
