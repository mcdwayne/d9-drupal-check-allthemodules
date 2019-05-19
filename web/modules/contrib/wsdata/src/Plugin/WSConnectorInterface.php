<?php

namespace Drupal\wsdata\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for Wsconnector plugin plugins.
 */
interface WSConnectorInterface extends ContainerFactoryPluginInterface {

  /**
   * Return the custom settings form for the wscall page.
   */
  public function getOptionsForm($options = []);

}
