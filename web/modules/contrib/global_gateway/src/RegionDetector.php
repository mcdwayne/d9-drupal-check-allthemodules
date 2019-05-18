<?php

namespace Drupal\global_gateway;

/**
 * Class RegionDetector.
 */
class RegionDetector {

  private static $providers = ['ip2country', 'smart_ip'];

  /**
   * Get list of providers.
   */
  public static function getProviders() {
    return static::$providers;
  }

  /**
   * Check if soft dependencies are meet.
   */
  public static function softDependenciesMeet() {
    foreach (static::getProviders() as $provider) {
      if (\Drupal::moduleHandler()->moduleExists($provider)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Return list of available detection providers.
   */
  public static function getDetectionProviders() {
    $providers = [];

    foreach (static::getProviders() as $provider) {
      if (\Drupal::moduleHandler()->moduleExists($provider)) {
        $providers[$provider] = \Drupal::moduleHandler()->getName($provider);
      }
    }
    return $providers;
  }

  /**
   * Wrapper function to get region code by ip using enabled detection module.
   */
  public static function detectRegionCode($provider = NULL) {
    $settings = \Drupal::configFactory()->get('global_gateway.settings');
    $enabled  = $settings->get('auto_detection_enabled');

    if (empty($provider)) {
      $provider = $settings->get('auto_detection_provider');
    }

    if (!$enabled || !$provider) {
      return FALSE;
    }

    $method = str_replace('_', '', $provider);
    $method = ucwords($method);
    $method = 'detectRegionCodeBy' . $method;
    $uid    = FALSE;

    if (\Drupal::moduleHandler()->moduleExists($provider)
      && method_exists(get_called_class(), $method)
    ) {
      if (\Drupal::currentUser()->isAuthenticated()) {
        $uid = \Drupal::currentUser()->id();
      }
      $ip = \Drupal::request()->getClientIp();
      return static::$method($uid, $ip);
    }

    return FALSE;
  }

  /**
   * Find region code by user IP using ip2country module.
   */
  public static function detectRegionCodeByIp2country($uid, $ip) {
    $region_code = FALSE;

    if ($uid) {
      $region_code = \Drupal::service('user.data')->get('ip2country', $uid, 'country_iso_code_2');
    }
    if (empty($region_code) || !empty($ip)) {
      $region_code = ip2country_get_country($ip);
    }
    return $region_code;
  }

  /**
   * Find region code by user IP using smartip module.
   */
  public static function detectRegionCodeBySmartip($uid, $ip) {
    $region_code = FALSE;

    if ($uid) {
      $user_data = \Drupal::service('user.data')->get('smart_ip', $uid);

      if (!empty($user_data['geoip_location']['location']['countryCode'])) {
        $region_code = $user_data['geoip_location']['location']['countryCode'];
      }
    }
    if (empty($region_code) || !empty($ip)) {
      $region_code = @\Drupal\smart_ip\SmartIp::query($ip)['countryCode'];
    }
    return $region_code;
  }

}
