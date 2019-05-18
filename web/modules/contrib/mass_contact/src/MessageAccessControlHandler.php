<?php

namespace Drupal\mass_contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler for the mass contact message entity.
 *
 * @see \Drupal\mass_contact\Entity\MassContactMessage
 */
class MessageAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

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
    $access = AccessResult::neutral();

    if ($operation === 'update') {
      // This is an archive, nobody can edit.
      return AccessResult::forbidden('No editing of archived mass contact messages is allowed.');
    }
    elseif ($operation === 'delete') {
      return AccessResult::forbidden('No deleting of archived mass contact messages is allowed.');
    }
    elseif ($operation === 'view') {
      $access = AccessResult::allowedIfHasPermission($account, 'mass contact view archived messages');
    }

    $admin_access = parent::checkAccess($entity, $operation, $account);

    return $access->orIf($admin_access)->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return parent::checkCreateAccess($account, $context, $entity_bundle)->orIf(AccessResult::allowedIfHasPermission($account, 'mass contact send messages'));
  }

}
