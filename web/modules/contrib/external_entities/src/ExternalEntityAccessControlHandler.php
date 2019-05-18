<?php

namespace Drupal\external_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines the access control handler for the node entity type.
 *
 * @see \Drupal\external_entities\Entity\ExternalEntity
 * @ingroup external_entity_access
 */
class ExternalEntityAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ExternalEntityAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // We don't treat the user label as privileged information, so this check
    // has to be the first one in order to allow labels for all users
    // to be viewed, including the special anonymous user.
    if ($operation === 'view label') {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    $account = $this->prepareUser($account);
    $result = parent::access($entity, $operation, $account, TRUE);
    if ($result->isForbidden()) {
      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($operation !== 'view') {
      /* @var \Drupal\external_entities\ExternalEntityTypeInterface $bundle */
      $bundle = $this->entityTypeManager
        ->getStorage($this->entityType->getBundleEntityType())
        ->load($entity->bundle());
      if ($bundle->isReadOnly()) {
        $result = AccessResult::forbidden()->cachePerPermissions();
      }
    }
    if ($result->isForbidden()) {
      return $return_as_object ? $result : $result->isAllowed();
    }
    $permission = $operation === 'update'
      ? "edit {$entity->bundle()} external entity"
      : "{$operation} {$entity->bundle()} external entity";
    $result = AccessResult::allowedIfHasPermission($account, $permission);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = [], $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();
    if ($result->isForbidden()) {
      return $return_as_object ? $result : $result->isAllowed();
    }
    /* @var \Drupal\external_entities\ExternalEntityTypeInterface $bundle */
    $bundle = $this->entityTypeManager
      ->getStorage($this->entityType->getBundleEntityType())
      ->load($entity_bundle);
    if ($bundle->isReadOnly()) {
      $result = AccessResult::forbidden()->cachePerPermissions();
    }
    if ($result->isForbidden()) {
      return $return_as_object ? $result : $result->isAllowed();
    }
    $permission = "create $entity_bundle external entity";

    $result = AccessResult::allowedIfHasPermission($account, $permission);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // We don't treat the user label as privileged information, so this check
    // has to be the first one in order to allow labels for all users
    // to be viewed, including the special anonymous user.
    if ($operation === 'view label') {
      return AccessResult::allowed();
    }

    $result = parent::checkAccess($entity, $operation, $account);
    if ($result->isForbidden()) {
      return $result;
    }
    if ($operation !== 'view') {
      /* @var \Drupal\external_entities\ExternalEntityTypeInterface $bundle */
      $bundle = $this->entityTypeManager
        ->getStorage($this->entityType->getBundleEntityType())
        ->load($entity->bundle());
      if ($bundle->isReadOnly()) {
        $result = AccessResult::forbidden()->cachePerPermissions();
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isForbidden()) {
      return $result;
    }
    if (!is_null($entity_bundle)) {
      return AccessResult::neutral()->cachePerPermissions();
    }
    /* @var \Drupal\external_entities\ExternalEntityTypeInterface $bundle */
    $bundle = $this->entityTypeManager
      ->getStorage($this->entityType->getBundleEntityType())
      ->load($entity_bundle);
    if ($bundle->isReadOnly()) {
      $result = AccessResult::forbidden()->cachePerPermissions();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $result = parent::checkFieldAccess($operation, $field_definition, $account, $items);
    if ($result->isForbidden()) {
      return $result;
    }
    if ($operation !== 'view') {
      $bundle_id = $field_definition->getTargetBundle();
      if ($bundle_id) {
        /* @var \Drupal\external_entities\ExternalEntityTypeInterface $bundle */
        $bundle = $this->entityTypeManager
          ->getStorage($this->entityType->getBundleEntityType())
          ->load($bundle_id);
        if ($bundle->isReadOnly()) {
          $result = AccessResult::forbidden();
        }
      }
    }
    return $result;
  }

}
