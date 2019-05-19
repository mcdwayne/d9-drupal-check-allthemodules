<?php

namespace Drupal\webform_permissions_by_term\Event;

/**
 * Class PermissionsByEntity.
 *
 * @package Drupal\webform_permissions_by_term\Event
 */
class PermissionsByEntityEvents {

  /**
   * Entity Field Value Access Denied event.
   *
   * This event occurs when the access to a referenced
   * content entity is denied for a user.
   *
   * @Event('Drupal/booking/Event/CreateTourbookBookingIframeUrlEvent')
   */
  const ENTITY_FIELD_VALUE_ACCESS_DENIED_EVENT = 'webform_permissions_by_term.entity_field_value_access_denied_event';

}
