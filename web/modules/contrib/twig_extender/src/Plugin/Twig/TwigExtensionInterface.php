<?php

namespace Drupal\twig_extender\Plugin\Twig;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Plugin interface.
 */
interface TwigExtensionInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Get type of the twig extension.
   */
  public function getType();

  /**
   * Get type of the twig extension.
   */
  public function getName();

  /**
   * Get type of the twig extension.
   */
  public function getFunction();

  /**
   * Get type of the twig extension.
   */
  public function register();

}
