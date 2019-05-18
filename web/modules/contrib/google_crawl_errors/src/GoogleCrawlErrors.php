<?php

namespace Drupal\google_crawl_errors;

use Google_Client;
use Google_Service_Webmasters;

/**
 * Google Crawl Errors API class.
 */
class GoogleCrawlErrors {

  private $httpClient;

  private $oauthSecret;

  /**
   * GoogleCrawlErrors constructor.
   */
  public function __construct() {
    $config = \Drupal::service('config.factory')
      ->getEditable('google_crawl_errors.settings');
    $this->oauthSecret = json_decode($config->get('oauth_secret_json'), TRUE);

    $client = new Google_Client();

    $client->setAuthConfig($this->oauthSecret);
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
    $client->addScope(Google_Service_Webmasters::WEBMASTERS_READONLY);

    $access_token = json_decode($config->get('oauth_token_json'), TRUE);

    if ($access_token) {
      $client->setAccessToken($access_token);

      if ($client->isAccessTokenExpired()) {
        echo "Token expired, refreshed token.\n";
        $access_token = $client->refreshToken($access_token['refresh_token']);

        $access_token_json = json_encode($access_token);
        if ($access_token && is_array($access_token) && $access_token_json) {
          $config->set('oauth_token_json', $access_token_json)
            ->save();
          echo "Updated OAuth token.\n";
        }
      }

      $this->httpClient = $client->authorize();
    }
    else {
      die('Invalid access token.');
    }
  }

  /**
   * Get the path of the result files.
   *
   * @return string
   *   Full system path of result files.
   */
  public function getResultPath() {
    $path = \Drupal::service('file_system')->realpath("private://") . '/google_crawl_errors/results/';
    if (!file_exists(file_exists)) {
      mkdir($path, 0777, TRUE);
    }
    return $path;
  }

  /**
   * Get the Google API HTTP client object.
   *
   * @return object
   *   Google API HTTP client object.
   */
  public function getHttpClient() {
    return $this->httpClient;
  }

  /**
   * Get the Google API OAuth secret settings.
   *
   * @return array
   *   Google API OAuth secret settings.
   */
  public function getOauthSecret() {
    return $this->oauthSecret;
  }

  /**
   * Reads the appropriate result json file based on the specified parameters.
   *
   * @param string $site_id
   *   Site id.
   * @param string $category
   *   Error category code.
   * @param string $platform
   *   Report platform code.
   *
   * @return bool|mixed|string
   *   Result data array.
   */
  public function getResultData($site_id, $category, $platform) {

    $result_file = $this->getResultPath() . 'crawl-errors_' . $site_id . '_' . $category . '_' . $platform . '.json';
    $data = file_get_contents($result_file);
    if ($data) {
      $data = json_decode($data);
    }
    return $data;
  }

  /**
   * Retreive crawl errors data in JSON format from Google and save it as file.
   *
   * @param string $site_id
   *   Site id.
   * @param string $site_url
   *   Site url with protocal and non-standard port.
   * @param string $category
   *   Google crawl errors category code.
   * @param string $platform
   *   Google crawl errors platform code.
   */
  public function updateResultData($site_id, $site_url, $category, $platform) {
    if ($this->httpClient) {
      $response = $this->httpClient->get('https://www.googleapis.com/webmasters/v3/sites/' . urlencode($site_url) . '/urlCrawlErrorsSamples?category=' . $category . '&platform=' . $platform);

      if ($response) {
        $response_json = (string) $response->getBody();
        $fp = fopen($this->getResultPath() . 'crawl-errors_' . $site_id . '_' . $category . '_' . $platform . '.json', 'w');
        fwrite($fp, $response_json);
        fclose($fp);
        echo $site_id . "\n";
      }
    }
    else {
      die('Invalid access token.');
    }
  }

  /**
   * Prepare result content for outputing to template variables.
   *
   * @param bool|mixed|string $data
   *   Google crawl errors result data.
   * @param int $max_result
   *   Max number of result shown.
   *
   * @return bool|mixed|string
   *   Result content array.
   */
  public function prepareOutput($data, $max_result) {
    $contents = [];
    if ($data) {
      $i = 0;
      $result_count = count($data->urlCrawlErrorSample);

      while ($i < $max_result && $i < $result_count) {
        if (!empty($data->urlCrawlErrorSample[$i]->urlDetails->linkedFromUrls)) {
          $contents[$i]['linked_from_urls'] = $data->urlCrawlErrorSample[$i]->urlDetails->linkedFromUrls;
        }
        $contents[$i]['page_url'] = $data->urlCrawlErrorSample[$i]->pageUrl;
        $contents[$i]['last_crawled'] = $data->urlCrawlErrorSample[$i]->last_crawled;
        $i++;
      }
    }
    return $contents;
  }

}
