<?php

namespace Drupal\affiliates_connect;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides an interface for Affiliates network plugins.
 */
interface AffiliatesNetworkInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Collect response from the url.
   *
   * @param string $url
   *   An url where request is to be made
   * @param  array $options
   *   Containing headers
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   */
  public function get(string $url, array $options = []);

}
