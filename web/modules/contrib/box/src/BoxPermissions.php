<?php

namespace Drupal\box;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\box\Entity\BoxType;

/**
 * Provides dynamic permissions for boxes of different types.
 */
class BoxPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of box type permissions.
   *
   * @return array
   *   The box type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function boxTypePermissions() {
    $perms = [];
    // Generate box permissions for all box types.
    foreach (BoxType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of box permissions for a given box type.
   *
   * @param \Drupal\box\Entity\BoxType $type
   *   The box type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(BoxType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id box" => [
        'title' => $this->t('%type_name: Create new box', $type_params),
      ],
      "edit own $type_id box" => [
        'title' => $this->t('%type_name: Edit own box', $type_params),
      ],
      "edit any $type_id box" => [
        'title' => $this->t('%type_name: Edit any box', $type_params),
      ],
      "delete own $type_id box" => [
        'title' => $this->t('%type_name: Delete own box', $type_params),
      ],
      "delete any $type_id box" => [
        'title' => $this->t('%type_name: Delete any box', $type_params),
      ],
      "view $type_id revisions" => [
        'title' => $this->t('%type_name: View revisions', $type_params),
      ],
      "revert $type_id revisions" => [
        'title' => $this->t('%type_name: Revert revisions', $type_params),
        'description' => t('Role requires permission <em>view revisions</em> and <em>edit rights</em> for boxes in question, or <em>administer boxes</em>.'),
      ],
      "delete $type_id revisions" => [
        'title' => $this->t('%type_name: Delete revisions', $type_params),
        'description' => $this->t('Role requires permission to <em>view revisions</em> and <em>delete rights</em> for boxes in question, or <em>administer boxes</em>.'),
      ],
    ];
  }

}
