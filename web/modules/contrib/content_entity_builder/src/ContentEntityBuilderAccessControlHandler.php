<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Content entity.
 *
 * @ingroup content_entity_builder
 *
 * @see \Drupal\content_entity_builder\Entity\Content.
 */
class ContentEntityBuilderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return parent::access($entity, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array(), $return_as_object = FALSE) {
    return parent::createAccess($entity_bundle, $account, $context, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Check edit permission on update operation.
    $operation = ($operation == 'update') ? 'edit' : $operation;
    $permission = "";
    if ($operation == 'view') {
      $permission = 'access ' . $entity->getEntityTypeId() . ' content entity';
    }else {
      $permission = $operation . ' any ' . $entity->getEntityTypeId() . ' content entity';		
	}

    return AccessResult::allowedIfHasPermission($account, $permission);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permission = 'create ' . $this->entityTypeId . ' content entity';
    return AccessResult::allowedIfHasPermission($account, $permission);
  }

}
