<?php

namespace Drupal\carerix_form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class CarerixFormAccessControlHandler.
 *
 * @package Drupal\carerix_form
 */
class CarerixFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Deny delete access on default Carerix form.
    if ($operation == 'delete' && $entity->id() == CarerixFormFieldsOpen::NAME) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
