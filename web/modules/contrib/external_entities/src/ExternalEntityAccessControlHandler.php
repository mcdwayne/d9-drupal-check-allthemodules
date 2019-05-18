<?php

namespace Drupal\external_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Defines a generic access control handler for external entities.
 */
class ExternalEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral()) {
      $external_entity_type = $this->getExternalEntityType();
      if (!in_array($operation, ['view label', 'view']) && $external_entity_type->isReadOnly() && !$external_entity_type->isAnnotatable()) {
        $result = AccessResult::forbidden()
          ->addCacheableDependency($entity)
          ->addCacheableDependency($external_entity_type);
      }
      else {
        $result = AccessResult::allowedIfHasPermission($account, "{$operation} {$entity->getEntityTypeId()} external entity");
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);

    $external_entity_type = $this->getExternalEntityType();
    if ($external_entity_type && $external_entity_type->isReadOnly()) {
      $result = AccessResult::forbidden()
        ->addCacheableDependency($this->entityType)
        ->addCacheableDependency($external_entity_type);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $result = parent::checkFieldAccess($operation, $field_definition, $account, $items);

    // Do not display form fields when the external entity type is read-only,
    // with the exception of the annotation field (this allows editing the
    // annotation directly from the external entity form by using, for example,
    // the Inline Entity Form module).
    if ($operation === 'edit') {
      $external_entity_type = $this->getExternalEntityType();
      if ($external_entity_type && $external_entity_type->isReadOnly() && $field_definition->getName() !== ExternalEntityInterface::ANNOTATION_FIELD) {
        $result = AccessResult::forbidden()
          ->addCacheableDependency($this->entityType)
          ->addCacheableDependency($external_entity_type);
      }
    }

    return $result;
  }

  /**
   * Get the external entity type this handler is running for.
   *
   * @return \Drupal\external_entities\ExternalEntityTypeInterface|bool
   *   The external entity type config entity object, or FALSE if not found.
   */
  protected function getExternalEntityType() {
    return \Drupal::entityTypeManager()
      ->getStorage('external_entity_type')
      ->load($this->entityTypeId);
  }

}
