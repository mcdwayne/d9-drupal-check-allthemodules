<?php

namespace Drupal\icon\Plugin\Icon;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface IconProviderInterface
 */
interface IconProviderInterface extends ContainerAwareInterface, ContainerFactoryPluginInterface, DerivativeInspectionInterface, PluginInspectionInterface {

  /**
   * Retrieves the human readable label for the plugin.
   *
   * @return string
   *   The human readable label.
   */
  public function getLabel();

  /**
   * Retrieves the url for more information regarding the provider
   *
   * @return string
   *   The url.
   */
  public function getUrl();

  /**
   * Retrieves an array of settings passed to the renderer.
   *
   * @return array
   *   Array of settings.
   */
  public function getSettings();

}
