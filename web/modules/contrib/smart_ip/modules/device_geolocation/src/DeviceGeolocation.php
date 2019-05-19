<?php

/**
 * @file
 * Contains \Drupal\device_geolocation\DeviceGeolocation.
 */

namespace Drupal\device_geolocation;

use Drupal\device_geolocation\EventSubscriber\SmartIpEventSubscriber;
use Drupal\smart_ip\SmartIp;

/**
 * Device Geolocation static basic methods wrapper.
 *
 * @package Drupal\device_geolocation
 */
class DeviceGeolocation {

  /**
   * Check if user's location needs update via client side.
   *
   * @return bool
   */
  public static function isNeedUpdate() {
    $config = \Drupal::config(SmartIpEventSubscriber::configName());
    $frequencyCheck = $config->get('frequency_check');
    $timestamp      = SmartIp::getSession('device_geolocation_last_attempt');
    if ($frequencyCheck === NULL) {
      // User's device geolocation checking is set disabled.
      return FALSE;
    }
    elseif (is_null(SmartIp::getSession('device_geolocation')) && empty($timestamp)) {
      // The user has not allowed to share his/her location yet then return that
      // user's location needs update and start the timer.
      SmartIp::setSession('device_geolocation_last_attempt', \Drupal::time()->getRequestTime());
      return TRUE;
    }
    elseif (!empty($timestamp) && $frequencyCheck < (\Drupal::time()->getRequestTime() - $timestamp)) {
      // Return that user's location needs update and reset the timer.
      SmartIp::setSession('device_geolocation_last_attempt', \Drupal::time()->getRequestTime());
      return TRUE;
    }
    return FALSE;
  }

}
