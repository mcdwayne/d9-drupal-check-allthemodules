<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for entity counter conditions.
 */
interface EntityCounterConditionInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets the entity counter condition label.
   *
   * @return string
   *   The condition label.
   */
  public function getLabel();

  /**
   * Gets the entity counter condition entity type ID.
   *
   * This is the entity type ID of the entity passed to evaluate().
   *
   * @return string
   *   The condition's entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Evaluates the entity counter condition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity counter condition has been met, FALSE otherwise.
   */
  public function evaluate(EntityInterface $entity);

}
