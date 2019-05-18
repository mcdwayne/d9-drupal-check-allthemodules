<?php

namespace Drupal\select2boxes;

/**
 * Trait EntityCreationTrait.
 *
 * Helper trait with an implementation of method for creating entity.
 *
 * @package Drupal\select2boxes
 */
trait EntityCreationTrait {

  /**
   * Get an entity by its ID and entity type.
   *
   * @param string $entity_type_id
   *   Target entity type ID.
   * @param string $id
   *   Entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The matching entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getEntity($entity_type_id, $id) {
    return self::getEntityStorage($entity_type_id)->load($id);
  }

  /**
   * Get an entity by its properties, or create it if it doesn't exist.
   *
   * @param string $entity_type_id
   *   Target entity type ID.
   * @param array $values
   *   Values of the entity (eg. label, bundle).
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The matching or newly created entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getOrCreateEntity($entity_type_id, array $values) {
    // Prepare entity storage handler.
    $storage = self::getEntityStorage($entity_type_id);
    // Check for an existing entity.
    $entities = $storage->loadByProperties($values);
    if (!empty($entities)) {
      // Found some matches - just use the first one.
      $entity = reset($entities);
    }
    else {
      // No entity has been found - so create it.
      $entity = $storage->create($values);
      $entity->save();
    }
    return $entity;
  }

  /**
   * Get entity storage handler for specified entity type.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Entity storage handler.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private static function getEntityStorage($entity_type_id) {
    return \Drupal::entityTypeManager()->getStorage($entity_type_id);
  }

}
