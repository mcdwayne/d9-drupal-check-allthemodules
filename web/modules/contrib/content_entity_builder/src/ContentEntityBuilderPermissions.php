<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\content_entity_builder\Entity\ContentType;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class ContentEntityBuilderPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of content type permissions.
   *
   * @return array
   *   The content type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function contentTypePermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (ContentType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of content permissions for a given content entity type.
   *
   * @param \Drupal\content_entity_builder\Entity\ContentType $type
   *   The content type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(ContentType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "access $type_id content entity" => [
        'title' => $this->t('%type_name: Access content entity', $type_params),
      ],
      "create $type_id content entity" => [
        'title' => $this->t('%type_name: Create new content entity', $type_params),
      ],
      "edit any $type_id content entity" => [
        'title' => $this->t('%type_name: Edit any content entity', $type_params),
      ],
      "delete any $type_id content entity" => [
        'title' => $this->t('%type_name: Delete any content entity', $type_params),
      ],

    ];
  }

}
