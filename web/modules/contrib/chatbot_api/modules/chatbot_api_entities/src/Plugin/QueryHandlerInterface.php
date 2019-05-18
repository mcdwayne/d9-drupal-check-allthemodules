<?php

namespace Drupal\chatbot_api_entities\Plugin;

use Drupal\chatbot_api_entities\Entity\EntityCollectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines an interface for Query handler plugins.
 */
interface QueryHandlerInterface extends PluginInspectionInterface {

  /**
   * Query entities to be pushed.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $existing
   *   Existing entities already collected by other QueryHandler plugins. The
   *   plugin should add its entities to this list (or alter the existing ones).
   * @param \Drupal\chatbot_api_entities\Entity\EntityCollectionInterface $collection
   *   Collection object.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Loaded entities.
   */
  public function query(EntityTypeManagerInterface $entityTypeManager, array $existing, EntityCollectionInterface $collection);

  /**
   * Check if the query handler applies for the given entity type.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return bool
   *   TRUE if applies.
   */
  public function applies($entity_type_id);

}
