<?php

namespace Drupal\message_thread;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\message\Entity\Message;

/**
 * Provides an interface for storing and retrieving message statistics.
 */
interface MessageStatisticsInterface {

  /**
   * Returns an array of ranking information for hook_ranking().
   *
   * @return array
   *   Array of ranking information as expected by hook_ranking().
   *
   * @see hook_ranking()
   * @see message_ranking()
   */
  public function getRankingInfo();

  /**
   * Read message statistics records for an array of entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of entities on which messageing is enabled, keyed by id.
   * @param string $entity_type
   *   The entity type of the passed entities.
   * @param bool $accurate
   *   (optional) Indicates if results must be completely up to date. If set to
   *   FALSE, a replica database will used if available. Defaults to TRUE.
   *
   * @return object[]
   *   Array of statistics records.
   */
  public function read(array $entities, string $entity_type, $accurate = TRUE);

  /**
   * Delete message statistics records for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which message statistics should be deleted.
   */
  public function delete(EntityInterface $entity);

  /**
   * Update or insert message statistics records after a message is added.
   *
   * @param \Drupal\message\Entity\Message $message
   *   The message added or updated.
   */
  public function update(Message $message);

  /**
   * Find the maximum number of messages for the given entity type.
   *
   * Used to influence search rankings.
   *
   * @param string $entity_type
   *   The entity type to consider when fetching the maximum message count for.
   *
   * @return int
   *   The maximum number of messages for and entity of the given type.
   *
   * @see message_update_index()
   */
  public function getMaximumCount($entity_type);

  /**
   * Insert an empty record for the given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The created entity for which a statistics record is to be initialized.
   */
  public function create(FieldableEntityInterface $entity);

}
