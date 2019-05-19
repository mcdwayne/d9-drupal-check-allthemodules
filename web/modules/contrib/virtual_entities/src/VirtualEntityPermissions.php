<?php

namespace Drupal\virtual_entities;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\virtual_entities\Entity\VirtualEntityType;

/**
 * Provides dynamic permissions for virtual entities of different types.
 */
class VirtualEntityPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of virtual entity type permissions.
   *
   * @return array
   *   The virtual entity type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function virtualEntityTypePermissions() {
    $perms = [];
    // Generate virtual entity permissions for all virtual entities types.
    foreach (VirtualEntityType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of entity permissions for a given virtual entity type.
   *
   * @param \Drupal\virtual_entities\Entity\VirtualEntityType $type
   *   The Virtual Entity type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(VirtualEntityType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id virtual entity" => [
        'title' => $this->t('%type_name: Create new virtual entity', $type_params),
      ],
      "edit $type_id virtual entity" => [
        'title' => $this->t('%type_name: Edit any virtual entity', $type_params),
      ],
      "delete $type_id virtual entity" => [
        'title' => $this->t('%type_name: Delete any virtual entity', $type_params),
      ],
      "view $type_id virtual entity" => [
        'title' => $this->t('%type_name: View any virtual entity', $type_params),
      ],
    ];
  }

}
