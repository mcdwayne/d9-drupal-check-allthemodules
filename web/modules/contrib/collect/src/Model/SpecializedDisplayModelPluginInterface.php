<?php
/**
 * @file
 * Contains \Drupal\collect\Model\SpecializedDisplayModelPluginInterface.
 */

namespace Drupal\collect\Model;

use Drupal\collect\TypedData\CollectDataInterface;

/**
 * Defines methods for model plugins that have special display code.
 */
interface SpecializedDisplayModelPluginInterface extends ModelPluginInterface {

  /**
   * Build a renderable array for the data of a Container.
   *
   * @param \Drupal\collect\TypedData\CollectDataInterface $data
   *   Typed data of this plugin.
   *
   * @return array
   *   A renderable array representing the content.
   */
  public function build(CollectDataInterface $data);

}
