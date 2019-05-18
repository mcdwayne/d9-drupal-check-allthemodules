<?php

namespace Drupal\eform;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Entity access control handler for EFormSubmission entities.
 */
class EFormSubmissionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'submit') {
      /** @var \Drupal\eform\entity\EFormsubmission $entity */
      $roles = $entity->getEFormType()->roles;
      $eform_roles = $account->getRoles();
      return AccessResult::allowed();

    }
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * @todo Abandoned?
   */
  public function checkSubmitAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowed();
  }

}
