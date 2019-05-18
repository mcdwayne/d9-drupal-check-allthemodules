<?php

namespace Drupal\lightbox_campaigns;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Lightbox Campaign entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class LightboxCampaignAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'edit':
        return AccessResult::allowedIfHasPermission(
          $account,
          'edit lightbox campaign'
        );

      case 'delete':
        return AccessResult::allowedIfHasPermission(
          $account,
          'delete lightbox campaign'
        );
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission(
      $account,
      'add lightbox campaign'
    );
  }

}
