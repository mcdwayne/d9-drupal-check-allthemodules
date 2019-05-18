<?php

namespace Drupal\icon\Plugin\Icon;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface IconRendererInterface
 */
interface IconRendererInterface extends ContainerAwareInterface, ContainerFactoryPluginInterface, DerivativeInspectionInterface, PluginInspectionInterface {

  /**
   * Retrieves the human readable label for the plugin.
   *
   * @return string
   *   The human readable label.
   */
  public function getLabel();

  /**
   * Retrieves the file where the preprocessing and theming hooks are defined.
   *
   * @return string
   *   The file name.
   */
  public function getFile();

  /**
   * Retrieves the path where the file is located.
   *
   * @return string
   *   The path to the file.
   */
  public function getPath();

}
