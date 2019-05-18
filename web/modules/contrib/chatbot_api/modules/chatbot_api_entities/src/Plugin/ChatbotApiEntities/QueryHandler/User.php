<?php

namespace Drupal\chatbot_api_entities\Plugin\ChatbotApiEntities\QueryHandler;

use Drupal\chatbot_api_entities\Entity\EntityCollectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a query handler that just uses entity query to limit as appropriate.
 *
 * @QueryHandler(
 *   id = "default:user",
 *   label = @Translation("User query")
 * )
 */
class User extends DefaultEntity {

  /**
   * {@inheritdoc}
   */
  protected function getQuery(EntityTypeManagerInterface $entityTypeManager, EntityCollectionInterface $collection, EntityTypeInterface $entity_type) {
    $query = parent::getQuery($entityTypeManager, $collection, $entity_type);
    $query->condition('status', 1);
    return $query;
  }

}
