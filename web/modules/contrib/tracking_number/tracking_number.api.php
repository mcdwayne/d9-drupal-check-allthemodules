<?php

/**
 * @file
 * Hooks provided by the Tracking Number module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter tracking number type plugin definitions.
 *
 * By implementing this hook, modules are able to override, alter, or remove any
 * existing tracking number type plugins.
 *
 * @param array $plugin_definitions
 *   An array of the tracking number type plugin definitions, keyed by plugin
 *   ID.
 *
 * @see Drupal\tracking_number\Plugin\TrackingNumberTypeManager
 */
function hook_tracking_number_type_info_alter(array &$plugin_definitions) {
  // Change the label for the United States Postal Service tracking number type.
  $plugin_definitions['usps']['label'] = t('USPS');

  // Remove the UPS type.
  unset($plugin_definitions['ups']);

  // To add a new tracking number type, create a new TrackingNumberType plugin
  // that extends Drupal\tracking_number\Plugin\TrackingNumberTypeBase.  See
  // Drupal\tracking_number\Plugin\TrackingNumberType\Usps or any of the other
  // tracking number types that ship with this module for example code.
}

/**
 * @} End of "addtogroup hooks".
 */
