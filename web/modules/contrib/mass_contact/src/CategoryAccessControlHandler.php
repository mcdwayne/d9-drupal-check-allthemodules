<?php

namespace Drupal\mass_contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler for the mass contact category entity.
 *
 * @see \Drupal\mass_contact\Entity\MassContactCategory.
 */
class CategoryAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $admin_access = parent::checkAccess($entity, $operation, $account);

    if ($operation === 'view') {
      $access = AccessResult::allowedIfHasPermission($account, "mass contact send to users in the {$entity->id()} category");
    }
    else {
      $access = AccessResultNeutral::neutral();
    }

    return $access->orIf($admin_access)->addCacheableDependency($entity);
  }

}
