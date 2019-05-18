<?php

namespace Drupal\chatbot_api_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an interface for defining Entity collection entities.
 */
interface EntityCollectionInterface extends ConfigEntityInterface {

  /**
   * Gets array of synonyms for the given entity if applicable.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to get synonyms for.
   *
   * @return string[]
   *   Synonyms.
   */
  public function getSynonyms(ContentEntityInterface $entity);

  /**
   * Generates the collection of entities using the query handlers and pushes.
   *
   * Calls each query handler in sequence to build up a list of entities then
   * passes them to each push handler to send to the remote chatbot endpoints.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function queryAndPush(EntityTypeManagerInterface $entityTypeManager);

  /**
   * Gets the entity type ID for the entities represented collection.
   *
   * @return string
   *   Entity type ID.
   */
  public function getCollectionEntityTypeId();

  /**
   * Gets the bundle for entities represented by the collection.
   *
   * @return string
   *   Bundle.
   */
  public function getCollectionBundle();

  /**
   * Sets configuration for handler.
   *
   * @param string $instance_id
   *   Handler instance.
   * @param array $configuration
   *   Configuration.
   *
   * @return $this
   */
  public function setQueryHandlerConfiguration($instance_id, array $configuration);

  /**
   * Sets configuration for handler.
   *
   * @param string $instance_id
   *   Handler instance.
   * @param array $configuration
   *   Configuration.
   *
   * @return $this
   */
  public function setPushHandlerConfiguration($instance_id, array $configuration);

  /**
   * Gets the name of the synonym field.
   *
   * @return string
   *   The name of the synonym field.
   */
  public function getSynonymField();

}
