<?php

/**
 * @file
 * JS Callback Handler APIs.
 */

use Drupal\Component\Utility\Xss;
use Drupal\js\Js;
use Drupal\js\JsResponse;

/**
 * Allows modules to alter delivery content with captured (printed) output.
 *
 * In some rare instances, some code uses "printable" functions (print,
 * print_r, echo, var_dump, etc.) that outputs directly to the browser. This
 * causes issues when data is encoded and compressed in the delivery callback
 * (e.g. gzip compresses the data returned from the callback, but is appended
 * with the printed output as well, thus resulting in a decoding error in the
 * browser).
 *
 * Instead of just discarding this captured data, this alter hook allows loaded
 * modules (defined by the callback) a chance to alter the delivery content
 * right before it's sent to the browser; in the event that the captured output
 * is useful for some reason.
 *
 * @param string $captured_content
 *   The captured content.
 * @param \Drupal\js\JsResponse $response
 *   A JsResponse based object.
 * @param \Drupal\js\Js $js
 *   The JS Callback Handler instance.
 */
function hook_js_captured_content_alter($captured_content, JsResponse $response, Js $js) {
  // Pass the captured output to the JSON array right before delivery.
  $json = $response->getData();
  $json['captured'] = Xss::filterAdmin($captured_content);
  $response->setData($json);
}
