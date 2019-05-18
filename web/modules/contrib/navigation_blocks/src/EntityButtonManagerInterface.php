<?php

namespace Drupal\navigation_blocks;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Interface definition for a entity button manager.
 *
 * @package Drupal\navigation_blocks
 */
interface EntityButtonManagerInterface {

  /**
   * Get the entity reference field options.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   Entity type.
   *
   * @return string[]
   *   Entity reference field options.
   */
  public function getEntityReferenceFieldOptions(EntityTypeInterface $entityType): array;

  /**
   * Get the entity type definition.
   *
   * @param string $entityTypeId
   *   Entity type id.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   Entity Type definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityType($entityTypeId): EntityTypeInterface;

  /**
   * Get the referenced entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content Entity.
   * @param string $fieldName
   *   Name of the entity reference field name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Referenced Entity.
   */
  public function getReferencedEntity(ContentEntityInterface $entity, string $fieldName): EntityInterface;

  /**
   * Get reversed entity reference entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $reversedEntityTypeId
   *   The entity type ID.
   * @param string $reversedBundle
   *   The entity type bundle.
   * @param string $reversedFieldName
   *   The entity reference field name.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reversed entity reference entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getReversedEntityReferenceEntity(EntityInterface $entity, string $reversedEntityTypeId, string $reversedBundle, string $reversedFieldName): EntityInterface;

  /**
   * Get the reversed entity reference field options.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   Entity type.
   *
   * @return string[]
   *   Reversed entity reference field options.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getReversedEntityReferenceFieldOptions(EntityTypeInterface $entityType): array;

}
