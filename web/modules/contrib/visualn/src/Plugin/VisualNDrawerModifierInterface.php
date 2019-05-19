<?php

namespace Drupal\visualn\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for VisualN Drawer Modifier plugins.
 */
interface VisualNDrawerModifierInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  // @todo: add a method(s) to apply modifier to given methods results
  //    see interface ImageEffectInterface
  //public function applyEffect(ImageInterface $image);

  /**
   * Returns a render array summarizing the configuration of the drawer modifier.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Returns the drawer modifier label.
   *
   * @return string
   *   The drawer modifier label.
   */
  public function label();

  /**
   * Returns the unique ID representing the drawer modifier.
   *
   * @return string
   *   The drawer modifier ID.
   */
  public function getUuid();

  /**
   * Returns the weight of the drawer modifier.
   *
   * @return int|string
   *   Either the integer weight of the drawer modifier, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this drawer modifier.
   *
   * @param int $weight
   *   The weight for this drawer modifier.
   *
   * @return $this
   */
  public function setWeight($weight);

}
