<?php
/**
 * @file
 * Contains \Drupal\collect\Model\DynamicModelTypedDataInterface.
 */

namespace Drupal\collect\Model;

use Drupal\collect\CollectContainerInterface;

/**
 * Defines methods for providing properties of dynamic models as typed data.
 *
 * Dynamic models may define different properties depending on the set of data
 * it represents. In generatePropertyDefinitions(), a piece of sample data may
 * be examined to deduce which properties are applicable.
 */
interface DynamicModelTypedDataInterface extends ModelTypedDataInterface {

  /**
   * Generate typed data definition for dynamic model.
   *
   * @param \Drupal\collect\CollectContainerInterface $collect_container
   *   (optional) Collect container item whose data has all the properties to be
   *   expected from all other data of this model.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The property definitions generated from the data.
   */
  public function generatePropertyDefinitions(CollectContainerInterface $collect_container);

}
