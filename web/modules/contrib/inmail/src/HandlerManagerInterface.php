<?php

namespace Drupal\inmail;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;

/**
 * Thin interface for the handler plugin manager.
 *
 * @ingroup handler
 */
interface HandlerManagerInterface extends DiscoveryInterface, FactoryInterface, FallbackPluginManagerInterface {

}
