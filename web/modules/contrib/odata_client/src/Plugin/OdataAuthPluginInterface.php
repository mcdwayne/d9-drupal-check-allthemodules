<?php

namespace Drupal\odata_client\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\odata_client\Entity\OdataServerInterface;
use Drupal\Core\DependencyInjection\Container;

/**
 * Defines an interface for Odata auth plugin plugins.
 */
interface OdataAuthPluginInterface extends PluginInspectionInterface {

  /**
   * Return the access token.
   *
   * @param \Drupal\odata_client\Entity\OdataServerInterface $config
   *   The config service.
   * @param \Drupal\Core\DependencyInjection\Container $serviceContainer
   *   The dependency container.
   */
  public function getAccessToken(OdataServerInterface $config,
    Container $serviceContainer);
}
