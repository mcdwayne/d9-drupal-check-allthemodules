<?php

namespace Drupal\simple_access;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

class SimpleAccessPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function nodeTypePermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "assign groups to $type_id nodes" => [
        'title' => $this->t('%type_name: Assign groups', $type_params),
      ],
      "assign profiles to $type_id nodes" => [
        'title' => $this->t('%type_name: Assign profiles', $type_params),
      ],
      "assign owner permissions for $type_id" => [
        'title' => $this->t('%type_name: Assign owner permissions', $type_params),
      ],
    ];
  }

}