<?php

namespace Drupal\media_expire;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaAccessControlHandler;

/**
 * Defines the access control handler for the media entity type.
 *
 * @see \Drupal\media\Entity\Media
 */
class MediaExpireAccessControlHandler extends MediaAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':

        /** @var \Drupal\Core\Access\AccessResult $accessResult */
        $accessResult = parent::checkAccess($entity, $operation, $account);

        if (!$accessResult->isAllowed()) {
          $bundle = \Drupal::entityTypeManager()
            ->getStorage('media_bundle')
            ->load($entity->bundle());

          return AccessResult::allowedIf(
              $account->hasPermission('view media') &&
              $bundle->getThirdPartySetting('media_expire', 'enable_expiring') &&
              $bundle->getThirdPartySetting('media_expire', 'fallback_media')
          );
        }

        return $accessResult;

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
