<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\entity_counter\Entity\EntityCounterInterface;

/**
 * Defines the interface for entity counter renders.
 *
 * @see \Drupal\entity_counter\Annotation\EntityCounterRenderer
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererManagerInterface
 * @see plugin_api
 */
interface EntityCounterRendererInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the entity counter renderer description.
   *
   * @return string
   *   The entity counter renderer description.
   */
  public function description();

  /**
   * Get the entity counter that this renderer is attached to.
   *
   * @return \Drupal\entity_counter\Entity\EntityCounterInterface
   *   A entity counter.
   */
  public function getEntityCounter();

  /**
   * Initialize entity counter renderer.
   *
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   A entity counter object.
   *
   * @return $this
   */
  public function setEntityCounter(EntityCounterInterface $entity_counter);

  /**
   * Returns the entity counter renderer label.
   *
   * @return string
   *   The entity counter renderer label.
   */
  public function label();

  /**
   * Returns the label of the entity counter renderer.
   *
   * @return int|string
   *   Either the integer label of the entity counter renderer, or an empty
   *   string.
   */
  public function getLabel();

  /**
   * Sets the label for this entity counter renderer.
   *
   * @param int $label
   *   The label for this entity counter renderer.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Apply the defined element render.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The updated render array.
   */
  public function render(array &$element);

  /**
   * Returns a short summary for the current renderer settings.
   *
   * @return string[]
   *   A short summary of the renderer settings.
   */
  public function getSummary();

}
