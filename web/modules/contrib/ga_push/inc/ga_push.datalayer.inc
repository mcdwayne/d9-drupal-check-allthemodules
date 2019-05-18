<?php

/**
 * @file
 * Datalayer js: method and functions.
 */

/**
 * GA Push Method callback: Datalayer (js).
 */
function ga_push_method_datalayer($push, $type, $options) {
  $push_info = [
    'push' => $push,
    'type' => $type,
  ];
  $_SESSION['ga_push_' . GA_PUSH_METHOD_DATALAYER_JS][] = $push_info;
}

/**
 * Send the ga push to JS on page load using Universal Analytics Event Tracking.
 */
function ga_push_method_datalayer_push() {
  $session_key = 'ga_push_' . GA_PUSH_METHOD_DATALAYER_JS;
  $script = NULL;
  if (isset($_SESSION[$session_key])) {
    $script = "var dataLayer = dataLayer || [];\n";

    // Process each push data that needs to be sent to Google Analytics.
    foreach ($_SESSION[$session_key] as $queued) {
      $push = $queued['push'];
      $type = $queued['type'];
      $script .= ga_push_method_datalayer_js_push_script($push, $type);
    }

    unset($_SESSION[$session_key]);
  }

  return $script;
}

/**
 * Generate the GA Push script code by type.
 *
 * @param array $push
 *   Push data.
 * @param string $type
 *   Push type.
 *
 * @return string
 *   Script code.
 */
function ga_push_method_datalayer_js_push_script(array $push, $type) {

  switch ($type) {
    case GA_PUSH_TYPE_EVENT:
      $script = _ga_push_method_datalayer_js_push_event_script($push);
      break;

    default:
      $script = NULL;
  }

  return $script;
}

/**
 * Generates the ga JS code for pushing an event to GA.
 *
 * @param array $push
 *   Event data.
 *
 * @return string
 *   JS code with push code.
 */
function _ga_push_method_datalayer_js_push_event_script(array $push) {

  // Optional: default values.
  $push += [
    'event' => '',
  ];

  // Convert to JS function.
  $script = _ga_push_method_datalayer_js_push_send_script($push);

  return $script;
}

/**
 * Generates the datalayer send JS code.
 *
 * @param array $push
 *   Data.
 *
 * @return string
 *   JS code with push code.
 */
function _ga_push_method_datalayer_js_push_send_script(array $push) {
  // Remove empty parameters:
  $push = array_filter($push);

  // Convert to JS function.
  $script = "dataLayer.push(" . json_encode($push) . ");\n";

  return $script;
}
