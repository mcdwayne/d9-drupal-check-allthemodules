<?php

/**
 * @file
 * Contains \Drupal\device_geolocation\Controller\DeviceGeolocationController.
 */

namespace Drupal\device_geolocation\Controller;

use Drupal\device_geolocation\DeviceGeolocation;
use Drupal\smart_ip\SmartIp;
use Drupal\smart_ip\SmartIpEvents;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Ajax callback handler for Device Geolocation module.
 *
 * @package Drupal\device_geolocation\Controller
 */
class DeviceGeolocationController extends ControllerBase {

  /**
   * Google Geocoding ajax callback function data handler.
   */
  public function saveLocation(Request $request) {
    // Check if the user permitted to share location.
    $shareLocation = SmartIp::getSession('smart_ip_user_share_location_permitted', TRUE);
    if ($shareLocation) {
      // Save only if user has permission to share location.
      $post = $request->request->all();
      if (isset($post['latitude']) && isset($post['longitude'])) {
        $data = [];
        SmartIp::setSession('device_geolocation', NULL);
        foreach ($post as $label => $address) {
          if (!empty($address)) {
            $label = Html::escape($label);
            $data[$label] = Html::escape($address);
            SmartIp::setSession('device_geolocation', TRUE);
          }
        }
        if (!empty($data)) {
          /** @var \Drupal\smart_ip\GetLocationEvent $event */
          $event     = \Drupal::service('smart_ip.get_location_event');
          $location  = $event->getLocation();
          $ipAddress = $location->get('ipAddress');
          $ipVersion = $location->get('ipVersion');
          $location->delete()
            ->setData($data)
            ->set('originalData', $data)
            ->set('ipAddress', $ipAddress)
            ->set('ipVersion', $ipVersion)
            ->set('timestamp', \Drupal::time()->getRequestTime());
          // Allow other modules to modify the acquired location from client
          // side via Symfony Event Dispatcher.
          \Drupal::service('event_dispatcher')
            ->dispatch(SmartIpEvents::DATA_ACQUIRED, $event);
          $location->save();
        }
      }
    }
    return new JsonResponse();
  }

  /**
   * Check for Geolocation attempt.
   */
  public function check(Request $request) {
    if (SmartIp::checkAllowedPage() && DeviceGeolocation::isNeedUpdate()) {
      $json = ['askGeolocate' => TRUE];
      /** @var \Drupal\smart_ip\SmartIpLocation $location */
      $location = \Drupal::service('smart_ip.smart_ip_location');
      // Send current user's geolocation to javascript.
      $json['device_geolocation'] = $location->getData(FALSE);
      $debugMode = SmartIp::isUserDebugMode();
      //$debugMode = FALSE;
      $json['device_geolocation']['debugMode']    = $debugMode;
      $json['device_geolocation']['askGeolocate'] = TRUE;
    }
    else {
      $json = ['askGeolocate' => FALSE];
    }
    return new JsonResponse($json);
  }

}
