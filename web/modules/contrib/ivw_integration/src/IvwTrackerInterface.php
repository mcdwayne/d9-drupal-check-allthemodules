<?php

namespace Drupal\ivw_integration;

/**
 * Interface IvwTrackerInterface.
 *
 * @package Drupal\ivw_integration
 */
interface IvwTrackerInterface {

  /**
   * Gets all tracking parameters.
   *
   * @return string[]
   *   Array containing 'st', 'mobile_st', 'cp', 'cpm',
   *   'sv' and 'mobile_sv' parameters.
   */
  public function getTrackingInformation();

}
