<?php

namespace Drupal\patreon_entity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\patreon_entity\Entity\PatreonEntityType;

/**
 * Provides dynamic permissions for patreon entities of different types.
 */
class PatreonEntityPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function patreonEntityTypePermissions() {
    $perms = [];
    // Generate entity permissions for all patreon entity types.
    foreach (PatreonEntityType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param PatreonEntityType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(PatreonEntityType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id content" => [
        'title' => $this->t('%type_name: Create new content', $type_params),
      ],
      "view published $type_id content" => [
        'title' => $this->t('%type_name: View published content', $type_params),
      ],
      "view unpublished $type_id content" => [
        'title' => $this->t('%type_name: View unpublished content', $type_params),
      ],
      "edit own $type_id content" => [
        'title' => $this->t('%type_name: Edit own content', $type_params),
      ],
      "edit any $type_id content" => [
        'title' => $this->t('%type_name: Edit any content', $type_params),
      ],
      "delete own $type_id content" => [
        'title' => $this->t('%type_name: Delete own content', $type_params),
      ],
      "delete any $type_id content" => [
        'title' => $this->t('%type_name: Delete any content', $type_params),
      ],
      "view $type_id revisions" => [
        'title' => $this->t('%type_name: View revisions', $type_params),
      ],
      "revert $type_id revisions" => [
        'title' => $this->t('%type_name: Revert revisions', $type_params),
        'description' => t('Role requires permission <em>view revisions</em> and <em>edit rights</em> for entities in question, or <em>administer patreon entity</em>.'),
      ],
      "delete $type_id revisions" => [
        'title' => $this->t('%type_name: Delete revisions', $type_params),
        'description' => $this->t('Role requires permission to <em>view revisions</em> and <em>delete rights</em> for entities in question, or <em>administer patreon entity</em>.'),
      ],
    ];
  }

}
