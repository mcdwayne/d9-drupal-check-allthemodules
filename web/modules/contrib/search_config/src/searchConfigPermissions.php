<?php

namespace Drupal\search_config;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic search permissions for nodes of different types.
 */
class searchConfigPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of search config permissions.
   *
   * @return array
   *   The search config permissions.
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function searchConfigPermissions() {
    $perms = [];
    // Generate search config permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {
      $type_id = $type->id();
      $perms["search $type_id content"] = [
        'title' => $this->t('%type_name: Search content of this type', ['%type_name' => $type->label()]),
      ];
    }

    return $perms;
  }
}
