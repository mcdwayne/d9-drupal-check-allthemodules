<?php

namespace Drupal\cascading_deletion;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Interface CascadingDeletionManagerInterface
 *
 * @package Drupal\cascading_deletion
 */
interface CascadingDeletionManagerInterface extends ContainerInjectionInterface {

  /**
   * @param $entityTypeId
   * @param $entityId
   */
  public function delete($entityTypeId, $entityId);
}