<?php

namespace Drupal\efap;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface ExtraFieldInterface.
 *
 * @package Drupal\efap
 */
interface ExtraFieldInterface {

  /**
   * Returns information that's provided to hook_entity_extra_field_info().
   *
   * @return array
   *   User defined data that's provided to hook_entity_extra_field_info().
   *   See hook_entity_extra_field_info() for more info on the structure
   *   of the array.
   */
  public function info() : array;

  /**
   * Returns a renderable array of the ExtraFieldBase.
   *
   * @param array $build
   *   Build information.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity displayed.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The EntityViewDisplayInterface used to display the Entity.
   * @param string $viewMode
   *   View mode of the Entity.
   *
   * @return array
   *   Drupal renderable array.
   */
  public function view(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $viewMode) : array;

}
