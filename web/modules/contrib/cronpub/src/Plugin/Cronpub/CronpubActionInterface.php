<?php

/**
 * @file
 * Contains \Drupal\cronpub\CronpubEntityInterface.
 */

namespace Drupal\cronpub\Plugin\Cronpub;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\Entity;

/**
 * Provides an interface for defining Cronpub entity entities.
 *
 * @ingroup cronpub
 */
interface CronpubActionInterface
{

  /**
   * Action to execute on date start.
   *
   * @param ContentEntityBase $entity
   *   Entity to operate on.
   * @return mixed
   */
  public function startAction(ContentEntityBase $entity);

  /**
   * Action to execute on date start.
   *
   * @param ContentEntityBase $entity
   *   Entity to operate on.
   * @return mixed
   */
  public function endAction(ContentEntityBase $entity);
}
