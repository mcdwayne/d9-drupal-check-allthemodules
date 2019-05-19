<?php

namespace Drupal\xero_contact_sync;

final class XeroContactSyncEvents {

  /**
   * The name of the event fired when we are going to perform a Xero contact
   * creation.
   *
   * This event allows you to customize the Contact before a Xero contact is
   * being saved. The event listener method receives a
   * \Drupal\xero_contact_sync\XeroContactSyncEvent instance.
   *
   * @Event
   *
   * @see \Drupal\xero_contact_sync\XeroContactSyncEvent
   */
  const SAVE = 'xero_contact_sync.save';

}
