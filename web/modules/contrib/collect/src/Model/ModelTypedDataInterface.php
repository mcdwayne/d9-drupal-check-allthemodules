<?php
/**
 * @file
 * Contains \Drupal\collect\Model\ModelTypedDataInterface.
 */

namespace Drupal\collect\Model;

/**
 * Defines methods for providing property definitions of a model.
 */
interface ModelTypedDataInterface {

  /**
   * Returns definitions of all properties of the data.
   *
   * @return \Drupal\collect\Model\PropertyDefinition[]
   *   Property definition objects.
   */
  public function getPropertyDefinitions();

  /**
   * Defines properties that are applicable to all data using this model plugin.
   *
   * @return \Drupal\collect\Model\PropertyDefinition[]
   *   A list of property definitions, whose data definitions should be
   *   appropriately created with the static create() method on a class
   *   implementing DataDefinitionInterface.
   */
  public static function getStaticPropertyDefinitions();

}
