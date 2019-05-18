<?php


namespace Drupal\data_api;


/**
 * Class BadDateClass
 *
 * A class missing format() methdo.
 *
 * @package Drupal\data_api
 */
class BadDateClass {

  public function getTimeZone() {
    return 'UTC';
  }
}
