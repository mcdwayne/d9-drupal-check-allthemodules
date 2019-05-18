<?php

/**
 * @file
 * Contains \Drupal\accessibility\TermAccessController.
 */

namespace Drupal\accessibility;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the accessibility test entity.
 *
 * @see \Drupal\accessibility\Entity\AccessibilityTest
 */
class AccessibilityTestAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode = null, AccountInterface $account = NULL) {
    if(!$account) {
      $account = \Drupal::currentUser();
    }
    if($operation == 'view') {
      return $account->hasPermission('view accessibility tests');
    }
    return $account->hasPermission('administer accessibility tests');
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('administer accessibility tests');
  }

}
