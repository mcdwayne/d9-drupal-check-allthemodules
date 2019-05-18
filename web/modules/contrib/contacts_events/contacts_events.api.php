<?php

/**
 * @file
 * Describes hooks for Contacts Events.
 */

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow denying of bookings with a reason displayed to the user.
 *
 * The first reason provided by an implementation will be displayed to the user.
 *
 * @param \Drupal\contacts_events\Entity\EventInterface $event
 *   The event we are attempting to book for.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account the booking is for.
 *
 * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
 *   A denial reason, or NULL to not deny.
 */
function hook_contacts_events_deny_booking(\Drupal\contacts_events\Entity\EventInterface $event, \Drupal\Core\Session\AccountInterface $account) {
  // Don't allow administrators to book for events.
  if (in_array('administrator', $account->getRoles())) {
    return new TranslatableMarkup('Administrators are now allowed to book on events.');
  }

  return NULL;
}

/**
 * @} End of "addtogroup hooks".
 */
