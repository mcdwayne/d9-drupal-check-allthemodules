<?php

namespace Drupal\aws_cloud\Controller\Ec2;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Instance entity.
 *
 * @see \Drupal\aws_cloud\Entity\Ec2\Instance\Entity\Instance.
 */
class InstanceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // First check for cloud_context access.
    if (!AccessResult::allowedIfHasPermission($account, 'view ' . $entity->getCloudContext())->isAllowed()) {
      return AccessResult::neutral();
    }
    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view any aws cloud instance')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf($account->hasPermission('view own aws cloud instance') && ($account->id() == $entity->getOwner()->id()));
        }
      case 'update':
      case 'edit':
        if ($account->hasPermission('edit any aws cloud instance')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf($account->hasPermission('edit own aws cloud instance') && ($account->id() == $entity->getOwner()->id()));
        }
      case 'delete':
        if ($account->hasPermission('delete any aws cloud instance')) {
          return AccessResult::allowed();
        }
        else {
          return AccessResult::allowedIf($account->hasPermission('delete own aws cloud instance') && ($account->id() == $entity->getOwner()->id()));
        }
      case 'start':
        if ($entity->getInstanceState() == 'stopped') {
          if ($account->hasPermission('edit any aws cloud instance')) {
            return AccessResult::allowed();
          }
          else {
            return AccessResult::allowedIf($account->hasPermission('edit own aws cloud instance') && ($account->id() == $entity->getOwner()->id()));
          }
        }
        break;

      case 'stop':
        if ($entity->getInstanceState() == 'running') {
          if ($account->hasPermission('edit any aws cloud instance')) {
            return AccessResult::allowed();
          }
          else {
            return AccessResult::allowedIf($account->hasPermission('edit own aws cloud instance') && ($account->id() == $entity->getOwner()->id()));
          }
        }
        break;

      case 'reboot':
        if ($entity->getInstanceState() == 'running') {
          if ($account->hasPermission('edit any aws cloud instance')) {
            return AccessResult::allowed();
          }
          else {
            return AccessResult::allowedIf($account->hasPermission('edit own aws cloud instance') && ($account->id() == $entity->getOwner()->id()));
          }
        }
        break;

      case 'associate_elastic_ip':
        if ($entity->getInstanceState() == 'stopped' && aws_cloud_can_attach_ip($entity) == TRUE && count(aws_cloud_get_available_elastic_ips($entity->getCloudContext()))) {
          if ($account->hasPermission('edit any aws cloud instance')) {
            return AccessResult::allowed();
          }
          else {
            return AccessResult::allowedIf($account->hasPermission('edit own aws cloud instance') && ($account->id() == $entity->getOwner()->id()));
          }
        }
        break;
    }
    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add aws cloud instance');
  }

}
