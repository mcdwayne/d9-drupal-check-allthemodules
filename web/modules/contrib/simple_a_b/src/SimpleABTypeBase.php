<?php

namespace Drupal\simple_a_b;

use Drupal\Component\Plugin\PluginBase;

/**
 * Defines an plugin base for entity types.
 */
class SimpleABTypeBase extends PluginBase implements SimpleABTypeInterface {

  /**
   * Return the name of the entity type.
   *
   * @return string
   *   Returns id
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Return the name of the entity type.
   *
   * @return string
   *   Returns name
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * Returns the entity type.
   *
   * @return mixed
   *   Returns entity type
   */
  public function getEntityType() {
    return $this->pluginDefinition['entityTargetType'];
  }

  /**
   * Returns the entity description.
   *
   * @return mixed
   *   Returns entity description
   */
  public function getEntityDescription() {
    return $this->pluginDefinition['entityDescription'];
  }

}
