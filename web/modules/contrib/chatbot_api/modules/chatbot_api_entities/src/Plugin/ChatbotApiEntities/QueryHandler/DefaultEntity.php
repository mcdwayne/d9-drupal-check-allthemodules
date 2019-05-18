<?php

namespace Drupal\chatbot_api_entities\Plugin\ChatbotApiEntities\QueryHandler;

use Drupal\chatbot_api_entities\Entity\EntityCollectionInterface;
use Drupal\chatbot_api_entities\Plugin\QueryHandlerBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a query handler that just uses entity query to limit as appropriate.
 *
 * @QueryHandler(
 *   id = "default",
 *   label = @Translation("Default"),
 *   deriver = "Drupal\chatbot_api_entities\Plugin\Derivative\DefaultEntityDeriver"
 * )
 */
class DefaultEntity extends QueryHandlerBase {

  /**
   * Query entities to be pushed.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $existing
   *   Existing entities.
   * @param \Drupal\chatbot_api_entities\Entity\EntityCollectionInterface $collection
   *   The entity collection we need to push the entity for.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Loaded entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function query(EntityTypeManagerInterface $entityTypeManager, array $existing, EntityCollectionInterface $collection) {
    $entity_type_id = $collection->getCollectionEntityTypeId();
    $entity_type = $entityTypeManager->getDefinition($entity_type_id);
    $query = $this->getQuery($entityTypeManager, $collection, $entity_type);
    return $existing + $entityTypeManager->getStorage($entity_type_id)->loadMultiple($query->execute());
  }

  /**
   * Check if the query handler applies for the given entity type.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return bool
   *   TRUE if applies.
   */
  public function applies($entity_type_id) {
    return $this->pluginDefinition['entity_type'] === $entity_type_id;
  }

  /**
   * Generates the query for execution.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\chatbot_api_entities\Entity\EntityCollectionInterface $collection
   *   Collection.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity type.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Query to execute.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getQuery(EntityTypeManagerInterface $entityTypeManager, EntityCollectionInterface $collection, EntityTypeInterface $entity_type) {
    $query = $entityTypeManager->getStorage($entity_type->id())->getQuery();
    if ($entity_type->hasKey('bundle')) {
      $query->condition($entity_type->getKey('bundle'), $collection->getCollectionBundle());
    }
    if ($entity_type->hasKey('status')) {
      $query->condition($entity_type->getKey('status'), TRUE);
    }
    return $query;
  }

}
