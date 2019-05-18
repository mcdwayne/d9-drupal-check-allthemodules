<?php

namespace Drupal\odoo;

/**
 * Interface ConfigInterface
 *
 * @package Drupal\odoo
 */
interface ConfigInterface {

  /**
   * @param $key
   *   The key of the config parameter to return.
   *
   * @return mixed
   *   The value of the config parameter.
   */
  public function get($key);

}
