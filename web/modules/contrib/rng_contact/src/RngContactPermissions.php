<?php

namespace Drupal\rng_contact;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rng_contact\Entity\RngContactType;

/**
 * Provides dynamic permissions for rng_contact.
 */
class RngContactPermissions {

  use StringTranslationTrait;

  /**
   * Provides permissions for contact types.
   *
   * @return array
   *   An array of permissions.
   */
  public function rngContactTypes() {
    $permissions = [];

    foreach (RngContactType::loadMultiple() as $contact_type) {
      /** @var \Drupal\rng_contact\Entity\RngContactTypeInterface $contact_type */
      $id = $contact_type->id();

      $t_args = [
        '%name' => $contact_type->label(),
      ];

      $permissions["create $id rng_contact"] = [
        'title' => $this->t('%name: Create new contact', $t_args),
      ];
      $permissions["view own $id rng_contact"] = [
        'title' => $this->t('%name: View own contacts', $t_args),
      ];
      $permissions["view any $id rng_contact"] = [
        'title' => $this->t('%name: View any contacts', $t_args),
      ];
      $permissions["update own $id rng_contact"] = [
        'title' => $this->t('%name: Edit own contacts', $t_args),
      ];
      $permissions["update any $id rng_contact"] = [
        'title' => $this->t('%name: Edit any contacts', $t_args),
      ];
      $permissions["delete own $id rng_contact"] = [
        'title' => $this->t('%name: Delete own contacts', $t_args),
      ];
      $permissions["delete any $id rng_contact"] = [
        'title' => $this->t('%name: Delete any contacts', $t_args),
      ];
    }

    return $permissions;
  }

}
