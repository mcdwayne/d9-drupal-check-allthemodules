<?php

namespace Drupal\entity_gallery;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_gallery\Entity\EntityGalleryType;

/**
 * Provides dynamic permissions for entity galleries of different types.
 */
class EntityGalleryPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of entity gallery type permissions.
   *
   * @return array
   *   The entity gallery type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function entityGalleryTypePermissions() {
    $perms = array();
    // Generate entity gallery permissions for all entity gallery types.
    foreach (EntityGalleryType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of entity gallery permissions for a given entity gallery
   * type.
   *
   * @param \Drupal\entity_gallery\Entity\EntityGalleryType $type
   *   The entity gallery type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(EntityGalleryType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "create $type_id entity galleries" => array(
        'title' => $this->t('%type_name: Create new entity galleries', $type_params),
      ),
      "edit own $type_id entity galleries" => array(
        'title' => $this->t('%type_name: Edit own entity galleries', $type_params),
      ),
      "edit any $type_id entity galleries" => array(
        'title' => $this->t('%type_name: Edit any entity galleries', $type_params),
      ),
      "delete own $type_id entity galleries" => array(
        'title' => $this->t('%type_name: Delete own entity galleries', $type_params),
      ),
      "delete any $type_id entity galleries" => array(
        'title' => $this->t('%type_name: Delete any entity galleries', $type_params),
      ),
      "view $type_id revisions" => array(
        'title' => $this->t('%type_name: View revisions', $type_params),
      ),
      "revert $type_id revisions" => array(
        'title' => $this->t('%type_name: Revert revisions', $type_params),
        'description' => t('Role requires permission <em>view revisions</em> and <em>edit rights</em> for entity galleries in question, or <em>administer entity galleries</em>.'),
      ),
      "delete $type_id revisions" => array(
        'title' => $this->t('%type_name: Delete revisions', $type_params),
        'description' => $this->t('Role requires permission to <em>view revisions</em> and <em>delete rights</em> for entity galleries in question, or <em>administer entity galleries</em>.'),
      ),
    );
  }

}
