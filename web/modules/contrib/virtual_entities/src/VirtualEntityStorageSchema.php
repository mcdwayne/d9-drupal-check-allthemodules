<?php

namespace Drupal\virtual_entities;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Class VirtualEntityStorageSchema.
 *
 * @package Drupal\virtual_entities\Schema
 */
class VirtualEntityStorageSchema implements DynamicallyFieldableEntityStorageSchemaInterface {

  /**
   * {@inheritdoc}
   */
  public function requiresFieldStorageSchemaChanges(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresFieldDataMigration(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function finalizePurge(FieldStorageDefinitionInterface $storage_definition) {
    // TODO: Implement finalizePurge() method.
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityStorageSchemaChanges(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresEntityDataMigration(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeCreate(EntityTypeInterface $entity_type) {
    // TODO: Implement onEntityTypeCreate() method.
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeUpdate(EntityTypeInterface $entity_type, EntityTypeInterface $original) {
    // TODO: Implement onEntityTypeUpdate() method.
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityTypeDelete(EntityTypeInterface $entity_type) {
    // TODO: Implement onEntityTypeDelete() method.
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition) {
    // TODO: Implement onFieldStorageDefinitionCreate() method.
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original) {
    // TODO: Implement onFieldStorageDefinitionUpdate() method.
  }

  /**
   * {@inheritdoc}
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition) {
    // TODO: Implement onFieldStorageDefinitionDelete() method.
  }

}
