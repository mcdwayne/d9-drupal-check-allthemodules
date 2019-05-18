<?php

/**
 * @file
 * Describes API functions for tour module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allow modules to take actions when HelloSign makes an esignature callback.
 *
 * This hook is called whenever HelloSign makes a call to the site,
 * such as after a document has been signed. For details about the types of
 * events that happen and what this data structure contains, please refer to the
 * HelloSign docs (https://www.hellosign.com/api/eventsAndCallbacksWalkthrough).
 *
 * @param object $data
 *   The data sent by HelloSign.
 */
function hook_process_hellosign_callback($data) {
  // Get event info.
  $event_type = $data->event->event_type;

  if ($event_type == 'signature_request_signed') {
    \Drupal::logger('mymodule')->info(t('Someone has signed HelloSign signature request @id.', ['@id' => $data->signature_request->signature_request_id]));
  }
}

/**
 * @} End of "addtogroup hooks".
 */
