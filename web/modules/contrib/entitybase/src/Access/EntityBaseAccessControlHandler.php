<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseAccessControlHandler.
 */

namespace Drupal\entity_base\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_base\EntityBaseGrantDatabaseStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the entity type.
 */
class EntityBaseAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Constructs an object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $entityTypeId = $entity->getEntityTypeId();

    if ($account->hasPermission('bypass ' . $entityTypeId . ' access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }
    $result = parent::access($entity, $operation, $account, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $entityTypeId = $context['entity_type_id'];

    if ($account->hasPermission('bypass ' . $entityTypeId . ' access')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $entityTypeId = $entity->getEntityTypeId();

    // Fetch information from the entity object if possible.
    $status = $entity->isActive();
    $uid = $entity->getOwnerId();

    if ($operation === 'view' && $account->hasPermission('view any ' . $entityTypeId)) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    }

    if ($operation === 'view' && $account->hasPermission('view own ' . $entityTypeId) && $account->id() == $uid) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    }

    // @TODO Check if owners can view their own disabled entities.
    // if ($operation === 'view' && !$status && $account->hasPermission('view own disabled ' . $entityTypeId) && $account->isAuthenticated() && $account->id() == $uid) {
    //   return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
    // }

    // Evaluate entity grants.
    // return $this->grantStorage->access($entity, $operation, $account);

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($entity_bundle) {
      return AccessResult::allowedIf($account->hasPermission('create ' . $entity_bundle . ' ' . $context['entity_type_id']))->cachePerPermissions();
    }
    else {
      return AccessResult::allowedIf($account->hasPermission('create ' . $context['entity_type_id']))->cachePerPermissions();
    }
  }

}
