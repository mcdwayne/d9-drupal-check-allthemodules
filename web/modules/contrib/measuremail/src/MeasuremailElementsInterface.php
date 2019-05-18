<?php

namespace Drupal\measuremail;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for measuremail elements.
 *
 * @see \Drupal\measuremail\Annotation\MeasuremailElements
 * @see \Drupal\measuremail\MeasuremailElementsBase
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementInterface
 * @see \Drupal\measuremail\ConfigurableMeasuremailElementBase
 * @see \Drupal\measuremail\Plugin\MeasuremailElementsManager
 * @see plugin_api
 */
interface MeasuremailElementsInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Returns the measuremail element label.
   *
   * @return string
   *   The measuremail element label.
   */
  public function label();

  /**
   * Returns the unique ID representing the measuremail element.
   *
   * @return string
   *   The measuremail element ID.
   */
  public function getUuid();

  /**
   * Returns the weight of the measuremail element.
   *
   * @return int|string
   *   Either the integer weight of the measuremail element, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for this measuremail element.
   *
   * @param int $weight
   *   The weight for this measuremail element.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Prepares an element for rendering.
   *
   * @return array
   *   An array ready to be rendered as a form.
   */
  public function render();

}
