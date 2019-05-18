<?php

namespace Drupal\getresponse_forms;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for GetResponse forms.
 *
 * @see \Drupal\getresponse_forms\Entity\GetresponseForms.
 */
class GetresponseFormsFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'delete' || $operation == 'update') {
      return AccessResult::allowedIf($account->hasPermission('administer getresponse forms'));
    }
    return AccessResult::allowedIfHasPermission($account, 'access getresponse forms');
  }

}
