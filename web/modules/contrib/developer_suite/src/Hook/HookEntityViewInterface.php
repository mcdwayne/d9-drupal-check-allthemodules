<?php

namespace Drupal\developer_suite\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface HookEntityViewInterface.
 *
 * @package Drupal\developer_suite\Hook
 */
interface HookEntityViewInterface {

  /**
   * Executes the entity view hook.
   *
   * @param array &$build
   *   A renderable array representing the entity content. The module may add
   *   elements to $build prior to rendering. The structure of $build is a
   *   renderable array as expected by drupal_render().
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options configured for the
   *   entity components.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   */
  public function execute(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode);

}
