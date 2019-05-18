<?php

/**
 * @file
 * Contains \Drupal\freegeoip_views\FreegeoipGetValue.
 */

namespace Drupal\freegeoip_views;

use Drupal\freegeoip_views\FreegeoipGetService;

/**
 * Class FreegeoipGetValue.
 *
 * @package Drupal\freegeoip_views
 */
class FreegeoipGetValue {

 /**
  * \Drupal\freegeoip_views\FreegeoipGetService Object
  *
  * @var \Drupal\freegeoip_views\FreegeoipGetService
  */
  protected $freegeoip;

  /**
   * Constructor.
   */
  public function __construct(FreegeoipGetService $freegeoipServiceObj) {
    $this->freegeoip = $freegeoipServiceObj;
  }

  /**
   * providing the geoip value.
   *
   * @param mixed $keyval
   *
   * @return string
   */
  public function getFreegeoipValue($keyval = NULL) {
    if(isset($_SESSION['freegeoip']) && !empty($keyval)) {
      return json_decode($_SESSION['freegeoip'])[$keyval];
    }
    else if(isset($_SESSION['freegeoip']) && empty($keyval)) {
      return json_decode($_SESSION['freegeoip']);
    }
    else if (!isset($_SESSION['freegeoip']) && !empty($keyval)) {
      return $this->freegeoip->getFreegeoipDetails();
    }
  }

}
