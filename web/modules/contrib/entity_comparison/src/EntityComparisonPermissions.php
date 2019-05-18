<?php

namespace Drupal\entity_comparison;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_comparison\Entity\EntityComparison;

/**
 * Provides dynamic permissions for entity comparison of different types.
 */
class EntityComparisonPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of entity comparison type permissions.
   *
   * @return array
   *   The entity comparison type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function entityComparisonTypePermissions() {
    $perms = array();
    // Generate entity comparison permissions for all entity comparison types.
    foreach (EntityComparison::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of entity comparison permissions for a given entity comparison type.
   *
   * @param \Drupal\entity_comparison\Entity\EntityComparison $type
   *   The entity comparison type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(EntityComparison $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "use $type_id entity comparison" => array(
        'title' => $this->t('%type_name: Use entity comparison', $type_params),
      ),
    );
  }

}
