<?php

namespace Drupal\external_entities;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\external_entities\Entity\ExternalEntityType;

/**
 * Defines a class containing permission callbacks.
 */
class ExternalEntityPermissions {

  use StringTranslationTrait;

  /**
   * Gets an array of external entity type permissions.
   *
   * @return array
   *   The external entity type permissions.
   */
  public function externalEntityTypePermissions() {
    $permissions = [];

    // Generate permissions for all external entity types.
    foreach (ExternalEntityType::loadMultiple() as $external_entity_type) {
      $permissions += $this->buildPermissions($external_entity_type);
    }

    return $permissions;
  }

  /**
   * Builds a standard list of external entity permissions for a given type.
   *
   * @param \Drupal\external_entities\ExternalEntityTypeInterface $external_entity_type
   *   The external entity type.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(ExternalEntityTypeInterface $external_entity_type) {
    $id = $external_entity_type->id();
    $t_params = ['%type_name' => $external_entity_type->label()];

    return [
      "view {$id} external entity" => [
        'title' => $this->t('%type_name: View any external entity', $t_params),
      ],
      "view {$id} external entity collection" => [
        'title' => $this->t('%type_name: View external entity listing', $t_params),
      ],
      "create {$id} external entity" => [
        'title' => $this->t('%type_name: Create new external entity', $t_params),
      ],
      "update {$id} external entity" => [
        'title' => $this->t('%type_name: Edit any external entity', $t_params),
      ],
      "delete {$id} external entity" => [
        'title' => $this->t('%type_name: Delete any external entity', $t_params),
      ],
    ];
  }

}
