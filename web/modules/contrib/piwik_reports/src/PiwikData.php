<?php
/**
 * @file
 * Contains \Drupal\piwik_reports\PiwikData.
 */

namespace Drupal\piwik_reports;

use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;


class PiwikData {

  /**
   * Return piwik token auth from global or user.
   *
   * @return string
   *  Piwik token auth.
   */
  public static function getToken() {
    $config = \Drupal::config('piwik_reports.piwikreportssettings');
    $current_user = \Drupal::currentUser();
    $user_data = \Drupal::service('user.data')->get('piwik_reports', $current_user->id());
    $user_token = ($current_user->id() && isset($user_data['piwik_reports_token_auth']) ? $user_data['piwik_reports_token_auth'] : '');
    $token_auth = ($config->get('piwik_reports_token_auth') ? $config->get('piwik_reports_token_auth') : $user_token);

    return Html::escape($token_auth);
  }

  /**
   * Return server request results.
   *
   * @param string $query_url
   *  URL and query string to pass to piwik server.
   *
   * @return string
   *  Decoded server response.
   */
  public static function getResponse($query_url) {
    try {
      $response = \Drupal::httpClient()->get($query_url);
      $data = (string) $response->getBody();
      if (empty($data)) {
        return FALSE;
      }
      else {
        return Json::decode($data);
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Return a list of sites where statistics are accessible on piwik server.
   *
   * @param string $token_auth
   *   Piwik server token auth.
   *
   * @return array|string|bool
   *   Array of sites returned from Piwik reports API.
   */
  public static function getSites($token_auth) {
    $piwik_url = static::getUrl();
    if ($piwik_url) {
      return static::getResponse($piwik_url . 'index.php?module=API&method=SitesManager.getSitesWithAtLeastViewAccess&format=JSON&token_auth=' . $token_auth);
    }
    else {
      return FALSE;
    }

  }

  /**
   * Return Piwik server url.
   *
   * @return string
   *   Stored value of Piwik server URL.
   */
  public static function getUrl() {
    // Piwik Reports settings takes precedence over Matomo settings.
    $url = \Drupal::config('piwik_reports.piwikreportssettings')->get('piwik_server_url');
    if ($url == '') {
      if (\Drupal::moduleHandler()->moduleExists('matomo')) {
        //get https url if available first
        $url = \Drupal::config('matomo.settings')->get('url_http');
        $url = (\Drupal::config('matomo.settings')->get('url_https') ? \Drupal::config('matomo.settings')->get('url_https') : $url);
      }
    }
    if ($url == '') {
      \Drupal::messenger()->addWarning(t('Piwik server url is missing or wrong. Please ask your administrator to check Piwik Reports configuration.'));
    }
    return $url;
  }
}