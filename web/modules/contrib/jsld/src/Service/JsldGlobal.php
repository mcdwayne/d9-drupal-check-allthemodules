<?php

namespace Drupal\jsld\Service;

/**
 * Service to collect all data during request.
 */
class JsldGlobal {

  /**
   * {@inheritdoc}
   */
  protected $jsld;

  /**
   * JsldHelper constructor.
   */
  public function __construct() {
    global $_jsld_data;
    $this->jsld = $_jsld_data;
  }

  /**
   * Used for initialization of global $jsld variable.
   *
   * @see \Drupal\jsld\EventSubscriber\JsldSubscriber::jsldInit()
   */
  public function init() {
    $this->jsld = [];
  }

  /**
   * Return current value for global $jsld.
   *
   * @return array
   *   Array with all JSON-LD markup.
   */
  public function get() {
    return $this->jsld;
  }

  /**
   * Add array to global $jsld.
   *
   * @param array $structured_data
   *   Adds array with structured data to global JSON-LD array.
   */
  public function add($structured_data = []) {
    $this->jsld[] = $structured_data;
  }

}
