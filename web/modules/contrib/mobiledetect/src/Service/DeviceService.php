<?php

/**
 * @file
 * Contains Drupal\mobiledetect\Service\DeviceService.
 */

namespace Drupal\mobiledetect\Service;

use MobileDetect;

/**
 * Drupal service for detecting mobile devices.
 */
class DeviceService {

  /**
   * Check if the device is mobile.
   *
   * @var bool
   */
  protected $is_mobile;

  /**
   * Check if the device is a tablet.
   *
   * @var bool
   */
  protected $is_tablet;

  /**
   * Get the User-Agent if it's set.
   *
   * @var string|null
   */
  protected $user_agent;

  /**
   * Some detection result values.
   *
   * @var object
   */
  protected $detect;

  /**
   * Constructs the DeviceService.
   */
  public function __construct() {
    $mobile_detect = new MobileDetect;

    $this->detect = $mobile_detect;
    $this->is_mobile = $mobile_detect->isMobile();
    $this->is_tablet = $mobile_detect->isTablet();
    $this->user_agent = $mobile_detect->getUserAgent();
  }

  /**
   * Check if the device is mobile.
   *
   * @return bool
   *   TRUE if a device as mobile detected.
   */
  public function isMobileDevice() {
    return $this->is_mobile;
  }

  /**
   * Check if the device is a tablet.
   *
   * @return bool
   *   TRUE if a tablet device detected.
   */
  public function isTabletDevice() {
    return $this->is_tablet;
  }

  /**
   * Get the User-Agent if it's set.
   *
   * @return string|null
   */
  public function getUserAgent() {
    return $this->user_agent;
  }

  /**
   * Some detection result values.
   *
   * @return object
   */
  public function detect() {
    return $this->detect;
  }

}
