<?php

namespace Drupal\contact_message_permissions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\contact\Entity\ContactForm;

/**
 * Defines dynamic permissions.
 *
 * @ingroup contact_message_permissions
 */
class PermissionsGenerator {
  use StringTranslationTrait;

  /**
   * Returns an array of entity type permissions.
   *
   * @return array
   *   The permissions.
   */
  public function entityPermissions() {
    return array_reduce(ContactForm::loadMultiple(), [$this, 'buildPermissions'], []);
  }

  /**
   * Builds a list of entity permissions for a given type.
   *
   * @param Array $carry
   *   The result of the previous iteration
   * @param ContactForm $form
   *   The entity type.
   *
   * @return array
   *   An array of permissions.
   */
  private function buildPermissions($carry, ContactForm $form) {
    return array_merge($carry, [
      'access messages for ' . $form->id() => [
        'title' => $this->t('Access messages created with %form form', ['%form' => $form->label()]),
      ],
    ]);
  }
}
