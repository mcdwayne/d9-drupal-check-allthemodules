<?php

/**
 * @file
 * Contains \Drupal\maestro\TemplateAccessController.
 */

namespace Drupal\maestro;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller for the Maestro Template entity.
 *
 * We set this class to be the access controller in Maestro Template's entity annotation.
 *
 * @see \Drupal\maestro\Entity\MaestroTemplate
 *
 * @ingroup maestro
 */
class MaestroTemplateAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view' && $account->hasPermission('administer maestro templates')) {
      return AccessResult::allowed();
    }
    
    if ($operation == 'update' && $account->hasPermission('administer maestro templates')) {
      return AccessResult::allowed();
    }
    
    if ($operation == 'delete' && $account->hasPermission('administer maestro templates')) {
      return AccessResult::allowed();
    }
    
    return parent::checkAccess($entity, $operation, $account);
  }

}
