<?php

namespace Drupal\webform_permissions_by_term\Service;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface WebformAccessCheckerInterface.
 *
 * @package Drupal\webform_permissions_by_term\Service
 */
interface WebformAccessCheckerInterface {

  /**
   * Checks if a user is allowed to access a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param bool|int $uid
   *   (Optional) Defaults to the uid of the current user.
   *
   * @return bool TRUE if access is allowed, otherwise FALSE.
   *   TRUE if access is allowed, otherwise FALSE.
   */
  public function isWebformAccessAllowed(ContentEntityInterface $entity, $uid = FALSE);

}
