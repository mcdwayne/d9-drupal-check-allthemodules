<?php

namespace Drupal\box_clone;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\box\Entity\BoxType;

class BoxClonePermissions {
  use StringTranslationTrait;

  /**
   * Returns an array of permissions.
   *
   * @return array
   *   The permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function cloneTypePermissions() {
    $perms = [];
    // Generate box permissions for all box types.
    /** @var \Drupal\box\Entity\BoxTypeInterface $type */
    foreach (BoxType::loadMultiple() as $type) {
      $type_id = $type->id();
      $type_params = ['%type_name' => $type->label()];
      $perms += [
        "clone {$type_id} box" => [
          'title' => $this->t('%type_name: clone box', $type_params),
        ],
      ];
    }
    return $perms;
  }

}
