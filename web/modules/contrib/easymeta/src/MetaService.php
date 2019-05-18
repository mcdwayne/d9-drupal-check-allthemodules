<?php

namespace Drupal\easymeta;

/**
 *
 */
class MetaService {
  protected $serviceValue;

  /**
   * When the service is created, set a value for the example variable.
   */
  public function __construct() {
    $this->serviceValue = \Drupal::config('easymeta.settings')->get('use_og_meta');;
  }

  /**
   * Return the value of the example variable.
   */
  public function getServiceMetaValue() {
    return $this->serviceValue;
  }

}
