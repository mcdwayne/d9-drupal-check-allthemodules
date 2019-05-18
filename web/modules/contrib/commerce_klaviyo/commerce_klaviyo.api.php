<?php

/**
 * @file
 * Hooks specific to the commerce_klaviyo module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the customer properties for the Klaviyo "identify" request.
 *
 * @param array $customer_properties
 *   The customer properties.
 */
function hook_commerce_klaviyo_identify_request_alter(array &$customer_properties) {
  $customer_properties['my_module_property'] = 'value';
}

/**
 * Allows altering parameters sent to Klaviyo as a "track" request.
 *
 * @param array $properties
 *   The order properties.
 * @param array $context
 *   An array of context. Available keys:
 *   event_name - the name of the event,
 *   klaviyo_request_properties - an instance of
 *   KlaviyoRequestPropertiesInterface.
 */
function hook_commerce_klaviyo_track_request_alter(array &$properties, array $context) {
  $properties['my_module_property'] = 'value';
}

/**
 * @} End of "addtogroup hooks".
 */
