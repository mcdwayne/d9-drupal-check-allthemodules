<?php

namespace Drupal\contact_emails;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the contact email storage.
 */
class ContactEmailStorage extends SqlContentEntityStorage implements ContactEmailStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function hasContactEmails($contact_form_id, $enabled_only = FALSE) {
    return !empty($this->loadValid($contact_form_id, $enabled_only));
  }

  /**
   * {@inheritdoc}
   */
  public function loadValid($contact_form_id, $enabled_only = FALSE) {
    $query = $this->getQuery();
    $query->condition('contact_form', $contact_form_id);
    if ($enabled_only) {
      $query->condition('status', TRUE);
    }
    $result = $query->execute();

    if (!empty($result)) {
      return $this->loadMultiple($result);
    }
    return FALSE;
  }

}
