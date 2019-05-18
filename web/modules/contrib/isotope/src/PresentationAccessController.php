<?php

/**
 * @file
 * Contains \Drupal\isotope\PresentationAccessController.
 */

namespace Drupal\isotope;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the presentation entity.
 *
 * We set this class to be the access controller in Presentation's entity annotation.
 *
 * @see \Drupal\isotope\Entity\Presentation
 *
 * @ingroup isotope
 */
class PresentationAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // The $opereration parameter tells you what sort of operation access is
    // being checked for.
    if ($operation == 'view') {
      return TRUE;
    }
    // @TODO: Improve security.
    return parent::checkAccess($entity, $operation, $account);
  }

}
