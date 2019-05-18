<?php

namespace Drupal\funnelback;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Service class for funnelback client.
 */
class FunnelbackClient {

  protected $debugNone = 'none';
  protected $debugLog = 'log';
  protected $debugVerbose = 'verbose';

  /**
   * Make a request.
   *
   * @param string $baseUrl
   *   The base URL for the request.
   * @param string $apiPath
   *   The api path from the base URL.
   * @param array $requestParams
   *   The request parameters.
   *
   * @return object
   *   The response object.
   */
  public function request($baseUrl, $apiPath, array $requestParams) {

    // Build the search URL with query params.
    $url = url($baseUrl . $apiPath, ['query' => $requestParams]);

    $url = FunnelbackQueryString::funnelbackQueryNormaliser($url);

    // Make the request.
    $response = drupal_http_request($url);

    $this->debug('Requesting url: %url. Response %response. Template %template', [
      '%url' => $url,
      '%response' => $response->code,
      '%template' => ($apiPath == 's/search.json') ? t('Default') : t('Custom'),
    ]);

    return $response;
  }

  /**
   * Helper to log debug messages.
   *
   * @param string $message
   *   A message, suitable for watchdog().
   * @param array $args
   *   (optional) An array of arguments, as per watchdog().
   * @param int $logLevel
   *   (optional) The watchdog() log level. Defaults to WATCHDOG_DEBUG.
   */
  public function debug($message, array $args = [], $logLevel = 7) {

    $debug = variable_get('funnelback_debug_mode', $this->debugNone);
    if ($debug == $this->debugLog) {
      watchdog('funnelback', $message, $args, $logLevel);
    }
    elseif ($debug == $this->debugVerbose) {
      $string = new FormattableMarkup($message, $args);
      if ($logLevel >= WATCHDOG_ERROR) {
        $messageLevel = 'error';
      }
      else {
        $messageLevel = 'status';
      }
      drupal_set_message($string, $messageLevel);
    }
  }

}
