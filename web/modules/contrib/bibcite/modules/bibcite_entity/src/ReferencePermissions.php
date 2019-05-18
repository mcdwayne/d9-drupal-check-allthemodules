<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\bibcite_entity\Entity\ReferenceType;

/**
 * Provides dynamic permissions for References of different types.
 */
class ReferencePermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of Reference type permissions.
   *
   * @return array
   *   The \Drupal\bibcite_entity\Entity\Reference type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function referenceTypePermissions() {
    $perms = [];
    // Generate Reference permissions for all types.
    foreach (ReferenceType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of Reference permissions for a given type.
   *
   * @param ReferenceType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ReferenceType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id bibcite_reference" => [
        'title' => $this->t('%type_name: Create new Reference entity', $type_params),
      ],
      "edit own $type_id bibcite_reference" => [
        'title' => $this->t('%type_name: Edit own Reference entity', $type_params),
      ],
      "edit any $type_id bibcite_reference" => [
        'title' => $this->t('%type_name: Edit any Reference entity', $type_params),
      ],
      "delete own $type_id bibcite_reference" => [
        'title' => $this->t('%type_name: Delete own Reference entity', $type_params),
      ],
      "delete any $type_id bibcite_reference" => [
        'title' => $this->t('%type_name: Delete any Reference entity', $type_params),
      ],
    ];
  }

}
