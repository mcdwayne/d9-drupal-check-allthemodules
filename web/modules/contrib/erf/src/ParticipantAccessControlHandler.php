<?php

namespace Drupal\erf;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Participant entity.
 *
 * @see \Drupal\erf\Entity\Participant.
 */
class ParticipantAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\erf\Entity\ParticipantInterface $entity */
    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        if ($this->ownsRegistration($entity, $account)) {
          return AccessResult::allowedIfHasPermission($account, 'manage own registrations');
        }
        return AccessResult::allowedIfHasPermission($account, 'administer registrations');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage own registrations');
  }

  /**
   * Helper function for determining if a user owns a registration.
   */
  protected function ownsRegistration(EntityInterface $entity, AccountInterface $account) {
    /** @var \Drupal\erf\Entity\ParticipantInterface $entity */

    $session_registration_ids = \Drupal::service('erf.session')->getRegistrationIds();

    // Loop over the registrations for this participant using the computed
    // `registration` field.
    foreach ($entity->getRegistrations() as $registration) {
      // If the user account is logged in, return TRUE if they are the owner of
      // any associated registration.
      if ($account->isAuthenticated()) {
        if ($account->id() == $registration->getOwnerId()) {
          return TRUE;
        }
      }
      // If the user is anonymous, check the session to see if they created any
      // associated registration.
      else {
        if (in_array($registration->id(), $session_registration_ids)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
