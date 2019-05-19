<?php

namespace Drupal\swiper_slider;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Swiper slide entity.
 *
 * @see \Drupal\swiper_slider\Entity\SwiperSlider.
 */
class SwiperSliderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\swiper_slider\Entity\SwiperSlideInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished swiper slide entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published swiper slide entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit swiper slide entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete swiper slide entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add swiper slide entities');
  }

}
