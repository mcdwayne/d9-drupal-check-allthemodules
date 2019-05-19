<?php

namespace Drupal\simple_a_b;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for simple a/b test plugins.
 */
interface SimpleABTypeInterface extends PluginInspectionInterface {

  /**
   * Return the id of entity type.
   */
  public function getId();

  /**
   * Return the name of the entity type.
   */
  public function getName();

  /**
   * Returns the entity type.
   */
  public function getEntityType();

  /**
   * Returns the entity description.
   */
  public function getEntityDescription();

}
