<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Defines the interface for entity counter condition plugin managers.
 */
interface EntityCounterConditionManagerInterface extends CategorizingPluginManagerInterface {

  /**
   * Gets the filtered plugin definitions.
   *
   * @param array $entity_type_ids
   *   The entity type IDs.
   *
   * @return array
   *   The filtered plugin definitions.
   */
  public function getFilteredDefinitions(array $entity_type_ids);

}
