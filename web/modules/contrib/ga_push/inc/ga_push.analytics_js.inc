<?php

/**
 * @file
 * Universal Analytics js: method and functions.
 */

/**
 * GA Push Method callback: UTMP (js).
 */
function ga_push_method_analytics_js($push, $type, $options) {
  $push_info = [
    'push' => $push,
    'type' => $type,
  ];
  $_SESSION['ga_push_' . GA_PUSH_METHOD_ANALYTICS_JS][] = $push_info;
}

/**
 * Send the ga push to JS on page load using Universal Analytics Event Tracking.
 *
 * @return string
 *   Analytics script.
 */
function ga_push_method_analytics_js_push() {
  $session_key = 'ga_push_' . GA_PUSH_METHOD_ANALYTICS_JS;
  $script = NULL;
  if (isset($_SESSION[$session_key])) {
    $ga_config = \Drupal::config('google_analytics.settings');

    if (!empty($ga_config->get('account'))) {
      $script = "var ga = ga || [];\n";

      // Process each push data that needs to be sent to Google Analytics.
      foreach ($_SESSION[$session_key] as $queued) {
        $push = $queued['push'];
        $type = $queued['type'];

        $script .= ga_push_method_analytics_js_push_script($push, $type);
      }

      unset($_SESSION[$session_key]);
    }
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
function ga_push_method_analytics_js_push_script(array $push, $type) {

  switch ($type) {
    case GA_PUSH_TYPE_EVENT:
      $script = _ga_push_method_analytics_js_push_event_script($push);
      break;

    case GA_PUSH_TYPE_ECOMMERCE:
      $script = _ga_push_method_analytics_js_push_ecommerce_script($push);
      break;

    case GA_PUSH_TYPE_EXCEPTION:
      $script = _ga_push_method_analytics_js_push_send_script($push, 'exception');
      break;

    case GA_PUSH_TYPE_PAGEVIEW:
      $script = _ga_push_method_analytics_js_push_send_script($push, 'pageview');
      break;

    case GA_PUSH_TYPE_SOCIAL:
      $script = _ga_push_method_analytics_js_push_send_script($push, 'social');
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
function _ga_push_method_analytics_js_push_event_script(array $push) {

  // Optional: default values.
  $push += [
    'eventLabel' => '',
    'eventValue' => 1,
  ];

  // Convert to JS function.
  $script = _ga_push_method_analytics_js_push_send_script($push, 'event');

  return $script;
}

/**
 * Generates the ga JS code for pushing an ecommerce to GA.
 *
 * @param array $push
 *   Event data.
 *
 * @return string
 *   JS code with push code.
 */
function _ga_push_method_analytics_js_push_ecommerce_script(array $push) {

  // Include needed additional ga e-commerce library.
  $script = "ga('require', 'ecommerce', 'ecommerce.js');\n";

  // Transaction:
  $script .= "ga('ecommerce:addTransaction', " . json_encode($push['trans']) . ");\n";

  // Items:
  foreach ($push['items'] as $value) {
    $script .= "ga('ecommerce:addItem', " . json_encode($value) . ");\n";
  }

  // Sending!
  $script .= "ga('ecommerce:send');\n";

  return $script;
}

/**
 * Generates the ga send JS code.
 *
 * @param array $push
 *   Data.
 * @param string $hit_type
 *   The hit type: social, pageview...
 *
 * @return string
 *   JS code with push code.
 */
function _ga_push_method_analytics_js_push_send_script(array $push, $hit_type) {
  // Remove empty parameters:
  $push = array_filter($push);

  // Add the hitType: social.
  $push['hitType'] = $hit_type;

  // Convert to JS function.
  $script = "ga('send', " . json_encode($push) . ");\n";

  return $script;
}
