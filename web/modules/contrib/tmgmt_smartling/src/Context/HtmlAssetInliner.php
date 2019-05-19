<?php

namespace Drupal\tmgmt_smartling\Context;

//set_time_limit(300);

use Drupal;

class HtmlAssetInliner {

  # url to save complete page from
  private $url = '';
  # holds parsed html
  private $cookie = '';
  private $html = '';
  # holds DOM object
  private $dom = '';


  protected static $authError = array(
    "response" => array(
      "code" => "AUTHENTICATION_ERROR",
      "data" => array("baseUrl" => NULL, "body" => NULL, "headers" => NULL),
      "messages" => array("Authentication token is empty or invalid."),
    ),
  );

  protected static $uriMissingError = array(
    "response" => array(
      "code" => "VALIDATION_ERROR",
      "data" => array("baseUrl" => NULL, "body" => NULL, "headers" => NULL),
      "messages" => array("fileUri parameter is missing."),
    ),
  );


  /**
   *
   */
  public function __construct() {
    # suppress DOM parsing errors
    libxml_use_internal_errors(TRUE);

    $this->dom = new \DOMDocument();
    $this->dom->preserveWhiteSpace = FALSE;
    # avoid strict error checking
    $this->dom->strictErrorChecking = FALSE;
  }

  /**
   * Gets complete page data and returns generated string
   *
   * @param string $url - url to retrieve
   * @param string $cookie - cookie for authorization
   * @param bool $keepjs - whether to keep javascript
   * @param bool $compress - whether to remove extra whitespaces
   * @param array $settings
   * @param bool $debug
   *
   * @return string|void
   * @throws \Exception - throws an exception if provided url isn't in proper format
   */
  public function getCompletePage($url, $cookie = '', $keepjs = TRUE, $compress = FALSE, array $settings = [], $debug = FALSE) {
    # validate the URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      throw new \Exception('Invalid URL. Make sure to specify http(s) part.');
    }

    if (empty($url)) {
      if ($debug) {
        Drupal::logger('tmgmt_smartling_context_debug')->info('Url is missing.');
      }

      return self::$uriMissingError;
    }

    if (!$cookie) {
      if ($debug) {
        Drupal::logger('tmgmt_smartling_context_debug')->info('Auth error.');
      }

      return self::$authError;
    }

    $this->url = $url;
    $this->cookie = $cookie;

    $this->html = $this->getUrlContents($this->url,
      0,
      'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_8; en-US) AppleWebKit/534.10 (KHTML, like Gecko) Chrome/8.0.552.215 Safari/534.10',
      $settings,
      $debug
    );

    if (strlen($this->html) <= 300) {
      if ($debug) {
        Drupal::logger('tmgmt_smartling_context_debug')->info('Response is too small.');
      }

      return '';
    }

    return ($compress) ? $this->compress($this->html) : $this->html;
  }

  /**
   * Checks whether or not remote file exists
   *
   * @param $url
   *
   * @param $proj_settings
   * @param int $connection_timeout
   * @param int $timeout
   *
   * @return bool
   */
  public function remote_file_exists($url, $proj_settings, $connection_timeout = 500, $timeout = 5000) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    # don't download content
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connection_timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);

    $this->applySettingsToCurl($proj_settings, $ch);

    if (curl_exec($ch) !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Compresses generated page by removing extra whitespace
   */
  private function compress($string) {
    # remove whitespace
    return str_replace(array(
      "\r\n",
      "\r",
      "\n",
      "\t",
      '  ',
      '    ',
      '    '
    ), ' ', $string);
  }

  /**
   * Gets content for given url using curl and optionally using user agent
   *
   * @param $url
   * @param int $timeout
   * @param string $user_agent
   * @param array $settings
   * @param bool $debug
   *
   * @return int|mixed
   */
  public function getUrlContents(
    $url,
    $timeout = 0,
    $user_agent = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_8; en-US) AppleWebKit/534.10 (KHTML, like Gecko) Chrome/8.0.552.215 Safari/534.10',
    array $settings = [],
    $debug = FALSE
  ) {
    $crl = curl_init();

    if ($debug) {
      // Enable request headers into curl info array.
      curl_setopt($crl, CURLINFO_HEADER_OUT, TRUE);

      // Enable response headers to response.
      curl_setopt($crl, CURLOPT_HEADER, 1);
    }

    $this->applySettingsToCurl($settings, $crl);

    curl_setopt($crl, CURLOPT_URL, $url);
    curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1); # return result as string rather than direct output
    curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, $timeout); # set the timeout
    curl_setopt($crl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($crl, CURLOPT_COOKIE, $this->cookie);
    curl_setopt($crl, CURLOPT_USERAGENT, $user_agent); # set our 'user agent'

    curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, FALSE);

    $output = curl_exec($crl);

    if ($debug) {
      $curl_info = curl_getinfo($crl);
      $header_size = $curl_info['header_size'];
      $headers = substr($output, 0, $header_size);
      $output = substr($output, $header_size);

      Drupal::logger('tmgmt_smartling_context_debug')->info('Curl request info: @request_info:', [
        '@request_info' => print_r($curl_info, TRUE),
      ]);
      Drupal::logger('tmgmt_smartling_context_debug')->info('Curl response headers: @response_headers', [
        '@response_headers' => $headers,
      ]);
      Drupal::logger('tmgmt_smartling_context_debug')->info('Curl response body: @response_body', [
        '@response_body' => substr($output, 0, 500) . '*****',
      ]);
    }

    curl_close($crl);

    if (!$output) {
      return -1;
    }

    return $output;
  }

  /**
   * @param $proj_settings
   * @param $curl
   */
  private function applySettingsToCurl($proj_settings, $curl) {
    if (!empty($proj_settings['context_skip_host_verifying'])) {
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }

    if (!empty($proj_settings['enable_basic_auth'])) {
      curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($curl, CURLOPT_USERPWD, $proj_settings['basic_auth']['login'] . ':' . $proj_settings['basic_auth']['password']);
    }
  }

}
