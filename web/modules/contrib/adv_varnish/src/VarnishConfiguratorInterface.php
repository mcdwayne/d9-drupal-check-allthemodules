<?php
/**
 * @file
 * Contains \Drupal\adv_varnish\VarnishConfiguratorInterface.
 */

namespace Drupal\adv_varnish;


interface VarnishConfiguratorInterface {

  public function get($setting_key);

}
