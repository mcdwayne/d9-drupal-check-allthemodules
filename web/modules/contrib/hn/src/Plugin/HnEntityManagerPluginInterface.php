<?php

namespace Drupal\hn\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for Headless Ninja Entity Manager Plugin plugins.
 */
interface HnEntityManagerPluginInterface extends PluginInspectionInterface {

  /**
   * Returns if the entity is supported by this HnEntityManager.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE is the entity is supported, false if the entity isn't supported.
   */
  public function isSupported(EntityInterface $entity);

  /**
   * Handles the entity, and returns the content of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that will be handled.
   * @param string $view_mode
   *   The view mode that should be used to handle the entity.
   *
   * @return mixed
   *   A normalizable object.
   */
  public function handle(EntityInterface $entity, $view_mode = 'default');

}
