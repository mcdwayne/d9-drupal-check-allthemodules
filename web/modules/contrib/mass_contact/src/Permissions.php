<?php

namespace Drupal\mass_contact;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mass_contact\Entity\MassContactCategory;

/**
 * Defines per-category mass contact permissions.
 */
class Permissions {

  use StringTranslationTrait;

  /**
   * Generates per-category permissions.
   */
  public function categoryPermissions() {
    $permissions = [];
    /** @var \Drupal\mass_contact\Entity\MassContactCategoryInterface $category */
    foreach (MassContactCategory::loadMultiple() as $category) {
      $permissions["mass contact send to users in the {$category->id()} category"] = [
        'title' => $this->t('Send to users in the %category category', ['%category' => $category->label()]),
        'description' => $this->t('Allows the user to send messages to the users in the %category category', ['%category' => $category->label()]),
      ];
    }
    return $permissions;
  }

}
