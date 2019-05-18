<?php

namespace Drupal\crm_core_activity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\crm_core_contact\ContactInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines methods for CRM Activity entities.
 */
interface ActivityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Add a participant to the activity.
   *
   * @param \Drupal\crm_core_contact\ContactInterface $contact
   *   The contact to add as a participant.
   *
   * @return $this
   */
  public function addParticipant(ContactInterface $contact);

  /**
   * Check if participant exists in the activity.
   *
   * @param \Drupal\crm_core_contact\ContactInterface $contact
   *   The contact to check in activity participant.
   *
   * @return bool
   *   Returns TRUE if activity has a given contact/participant.
   */
  public function hasParticipant(ContactInterface $contact);

}
