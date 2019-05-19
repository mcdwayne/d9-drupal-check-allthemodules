<?php

/**
 * @file
 * Contains \Drupal\smart_ip\SmartIpLocation.
 */

namespace Drupal\smart_ip;

/**
 * Implements wrapper and utility methods for Smart IP's data location.
 *
 * @package Drupal\smart_ip
 */
class SmartIpLocation implements SmartIpLocationInterface {

  /**
   * All Smart IP location data.
   *
   * @var array
   */
  private $allData = [];

  /**
   * Original or raw location data from Smart IP data source.
   *
   * @var mixed
   */
  private $originalData;

  /**
   * The source ID.
   *
   * @var int
   */
  private $source;

  /**
   * The IP address.
   *
   * @var string
   */
  private $ipAddress;

  /**
   * The IP address version.
   *
   * @var string
   */
  private $ipVersion;

  /**
   * The country.
   *
   * @var string
   */
  private $country;

  /**
   * The ISO 3166 2-character country code.
   *
   * @var string
   */
  private $countryCode;

  /**
   * The city.
   *
   * @var string
   */
  private $city;

  /**
   * The region (FIPS).
   *
   * @var string
   */
  private $region;

  /**
   * The region code (FIPS).
   *
   * @var string
   */
  private $regionCode;

  /**
   * The postal / ZIP code.
   *
   * @var string
   */
  private $zip;

  /**
   * The longitute.
   *
   * @var float
   */
  private $longitute;

  /**
   * The latitute.
   *
   * @var float
   */
  private $latitute;

  /**
   * EU country flag.
   *
   * @var bool
   */
  private $isEuCountry;

  /**
   * GDPR country flag.
   *
   * @var bool
   */
  private $isGdprCountry;

  /**
   * The timestamp of the request made.
   *
   * @var integer
   */
  private $timestamp;

  /**
   * The time zone.
   *
   * @var string
   */
  private $timeZone;

  /**
   * Constructs Smart IP location.
   *
   * @param array $values
   *   Array of values for the Smart IP location.
   */
  public function __construct(array $values = []) {
    if (!empty($values)) {
      $this->setData($values);
    }
    else {
      // Populate its location variables with stored data.
      $this->getData(FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key = NULL) {
    if (!empty($key)) {
      $value = $this->{$key};
      if (!empty($value)) {
        return $value;
      }
      $this->getData(FALSE);
      if (isset($this->allData[$key])) {
        return $this->allData[$key];
      }
      else {
        return NULL;
      }
    }
    return $this->getData(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getData($update = FALSE) {
    if (empty($this->allData)) {
      if ($update) {
        SmartIp::updateUserLocation();
      }
      // Get current user's stored location from session.
      $data = SmartIp::getSession('smart_ip');
      $user = \Drupal::currentUser();
      if (empty($data['location']) && $user->id() != 0) {
        /** @var \Drupal\user\UserData $userData */
        $userData = \Drupal::service('user.data');
        // Get current user's stored location from user_data
        $data = $userData->get('smart_ip', $user->id(), 'geoip_location');
      }
      if (!empty($data['location'])) {
        // Populate the Smart IP location from current user's data or session.
        $this->setData($data['location']);
      }
    }
    return $this->allData;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->{$key} = $value;
    $this->allData[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $values = []) {
    foreach ($values as $key => $value) {
      $this->set($key, $value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Determine if saving location details of visitor from EU countries are
    // permitted.
    $euVisitorsDontSave = \Drupal::config('smart_ip.settings')
      ->get('eu_visitor_dont_save') && $this->isGdprCountry;
    // Check if the user permitted to share location.
    $shareLocation = SmartIp::getSession('smart_ip_user_share_location_permitted', TRUE);
    if ($shareLocation && !$euVisitorsDontSave) {
      // Save only if user has permission to share location.
      $user = \Drupal::currentUser();
      $uid = $user->id();
      $data['location'] = $this->allData;
      SmartIp::updateFields($data['location']);
      // Allow other modules to modify country list via
      // hook_smart_ip_user_save_alter().
      \Drupal::moduleHandler()->alter('smart_ip_user_save', $user, $data);
      // Save the Smart IP location in current user's session.
      SmartIp::setSession('smart_ip', $data);
      if ($uid != 0) {
        /** @var \Drupal\user\UserData $userData */
        $userData = \Drupal::service('user.data');
        // Save the Smart IP location to current user's user_data
        $userData->set('smart_ip', $uid, 'geoip_location', $data);
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $user = \Drupal::currentUser();
    $uid  = $user->id();
    $this->allData = [];
    // Save the Smart IP location in current user's session.
    SmartIp::setSession('smart_ip', NULL);
    if ($uid != 0) {
      /** @var \Drupal\user\UserData $userData */
      $userData = \Drupal::service('user.data');
      // Delete the Smart IP location in current user's user_data.
      $userData->delete('smart_ip', $uid, 'geoip_location');
    }
    return $this;
  }

}
