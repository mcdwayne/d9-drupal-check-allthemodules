<?php

/**
 * @file
 * UTMP: method and functions for Universal Tracking Measure Protocol.
 *
 * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
 */

use Drupal\Component\Utility\UrlHelper;

// Google UTMP Endpoint URL.
define('GA_PUSH_METHOD_UTMP_HOST_ENDPOINT', 'ssl.google-analytics.com/collect');

/**
 * GA Push Method callback: UTMP (php).
 */
function ga_push_method_utmp_php($push, $type, $options) {

  switch ($type) {

    case GA_PUSH_TYPE_EVENT:
      $event_data = ga_push_method_php_utmp_construct_event_request_data($push);
      // @TODO: Check response.
      ga_push_method_utmp_php_request($event_data, $options);
      break;

    case GA_PUSH_TYPE_ECOMMERCE:
      if (isset($push['trans']) && isset($push['items']) && count($push['items'])) {
        // Construct arrays.
        $ecommerce_data = ga_push_method_utmp_php_construct_ecommerce_request_data($push);

        // Sending the transaction:
        // @TODO: Check request response.
        ga_push_method_utmp_php_request($ecommerce_data['transaction'], $options);

        // Sending the items:
        foreach ($ecommerce_data['items'] as $item_data) {
          ga_push_method_utmp_php_request($item_data, $options);
        }
      }
      break;

    case GA_PUSH_TYPE_EXCEPTION:
      $exception_data = ga_push_method_php_utmp_construct_exception_request_data($push);
      // @TODO: Check response.
      ga_push_method_utmp_php_request($exception_data, $options);
      break;

    case GA_PUSH_TYPE_PAGEVIEW:
      $pageview_data = ga_push_method_php_utmp_construct_pageview_request_data($push);
      // @TODO: Check response.
      ga_push_method_utmp_php_request($pageview_data, $options);
      break;

    case GA_PUSH_TYPE_SOCIAL:
      $social_data = ga_push_method_php_utmp_construct_social_request_data($push);
      // @TODO: Check response.
      ga_push_method_utmp_php_request($social_data, $options);
      break;
  }
}

/**
 * Returns data array for a event tacking.
 *
 * @param array $push
 *   Array with event data.
 *
 * @return array
 *   Array with the event data preparad to be sent to GA.
 */
function ga_push_method_php_utmp_construct_event_request_data(array $push) {
  $push['t'] = 'event';
  return ga_push_analyticsjs_measurement_protocol_mapping($push);
}

/**
 * Returns data arrays for a eCommerce tacking.
 *
 * @param array $push
 *   Array with transaction and items data.
 *
 * @return array
 *   Array with two keys with eCommerce data preparad to be sent to GA:
 *     - transaction: transaction data to send to GA.
 *     - items: Array with data to send to GA for each item.
 */
function ga_push_method_utmp_php_construct_ecommerce_request_data(array $push) {
  $data = [];

  // Transaction data.
  $tdata = ['t' => 'transaction'];
  $tdata += ga_push_analyticsjs_measurement_protocol_mapping($push['trans']);
  $data['transaction'] = $tdata;

  // Items data.
  $data['items'] = [];
  foreach ($push['items'] as $value) {
    $idata = ['t' => 'item'];
    $idata += ga_push_analyticsjs_measurement_protocol_mapping($value);
    $data['items'][] = $idata;
  }

  return $data;
}

/**
 * Returns data array for a exception tracking.
 *
 * @param array $push
 *   Array with pageview data.
 *
 * @return array
 *   Array with the pageview data ready to be sent to GA.
 */
function ga_push_method_php_utmp_construct_exception_request_data(array $push) {
  $push['t'] = 'exception';
  return ga_push_analyticsjs_measurement_protocol_mapping($push);
}

/**
 * Returns data array for a pageview tracking.
 *
 * @param array $push
 *   Array with pageview data.
 *
 * @return array
 *   Array with the pageview data ready to be sent to GA.
 */
function ga_push_method_php_utmp_construct_pageview_request_data(array $push) {
  $push['t'] = 'pageview';
  return ga_push_analyticsjs_measurement_protocol_mapping($push);
}

/**
 * Returns data array for a social tracking.
 *
 * @param array $push
 *   Array with pageview data.
 *
 * @return array
 *   Array with the pageview data ready to be sent to GA.
 */
function ga_push_method_php_utmp_construct_social_request_data(array $push) {
  $push['t'] = 'social';
  return ga_push_analyticsjs_measurement_protocol_mapping($push);
}

/**
 * Sends tracking data to GA.
 *
 * @param array $data
 *   Data prepared for GA.
 * @param array $options
 *   Custom options from ga_push_add().
 * @param string $method
 *   Method to use (GET or POST).
 *
 * @see ga_push_add()
 * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
 */
function ga_push_method_utmp_php_request(array $data = [], array $options = [], $method = 'POST') {
  $ga_config = \Drupal::config('google_analytics.settings');

  $data['v'] = 1;
  // Options variables:
  $data['tid'] = !empty($options['tid']) ? $options['tid'] : $ga_config->get('account');
  $data['cid'] = !empty($options['cid']) ? $options['cid'] : ga_push_method_php_utmp_get_uuid();

  // Optional values:
  $optional = [
    'ua',
    'uip',
  ];
  foreach ($optional as $value) {
    if (array_key_exists($value, $options)) {
      $data[$value] = $options[$value];
    }
  }

  // @NOTE: Proxy Server:
  // Some environments are not able to send hits to Google Analytics directly.
  // To collect the IP and user agent from the client device and not the proxy
  // server, you can specify both values in the measurement protocol, and they
  // will override the values Google Analytics normally obtains from the
  // request headers.
  $user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : NULL;
  if (!empty($user_agent)) {
    $data += ['ua' => $user_agent];
  }

  $ip = \Drupal::request()->getClientIp();
  if (!empty($ip)) {
    $data += ['uip' => $ip];
  }

  $url      = 'https://' . GA_PUSH_METHOD_UTMP_HOST_ENDPOINT;
  $response = NULL;
  // Prevent browser cached data.
  $data += ['z' => mt_rand()];

  try {
    switch ($method) {
      case 'POST':
        $response = \Drupal::httpClient()
          ->post($url, ['form_params' => $data]);
        break;

      case 'GET':
        // @TODO better handling for query params.
        $response = \Drupal::httpClient()
          ->get($url . '?' . UrlHelper::buildQuery($data));
        break;
    }
  }
  catch (RequestException $exception) {
    watchdog_exception('ga_push', $exception);
    return FALSE;
  }
}

/**
 * Get an UUID ver 4 identifier form cookie or generates one on fallback.
 *
 * @return string
 *   UUID ver 4 identifier
 */
function ga_push_method_php_utmp_get_uuid() {
  if (isset($_COOKIE['_ga'])) {
    list($version, $domain_depth, $cid1, $cid2) = explode('.', $_COOKIE["_ga"], 4);
    return $cid1 . '.' . $cid2;
  }

  return ga_push_method_php_utmp_gen_uuid();
}

/**
 * Generates an UUID ver 4 identifier.
 *
 * Thanks to Andrew Mooer at php.net.
 * http://www.php.net/manual/en/function.uniqid.php#94959
 *
 * @return string
 *   UUID.
 */
function ga_push_method_php_utmp_gen_uuid() {
  return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    // 32 bits for "time_low".
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    // 16 bits for "time_mid".
    mt_rand(0, 0xffff),
    // 16 bits for "time_hi_and_version",
    // four most significant bits holds version number 4.
    mt_rand(0, 0x0fff) | 0x4000,
    // 16 bits, 8 bits for "clk_seq_hi_res",
    // 8 bits for "clk_seq_low",
    // two most significant bits holds zero and one for variant DCE1.1.
    mt_rand(0, 0x3fff) | 0x8000,
    // 48 bits for "node".
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
  );
}
