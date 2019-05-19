<?php

namespace Drupal\trash;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class TrashManager implements TrashManagerInterface {

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function trash(ContentEntityInterface $entity) {
    $entity->set('moderation_state', TrashModerationState::TRASHED);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function restore(ContentEntityInterface $entity) {
    $storage = $this->entityTypeManager->getStorage('content_moderation_state');
    $entities = $storage
      ->getQuery()
      ->allRevisions()
      ->condition('content_entity_type_id', $entity->getEntityTypeId())
      ->condition('content_entity_id', $entity->id())
      ->condition('content_entity_revision_id', $entity->getRevisionId(), '<')
      ->condition('moderation_state', TrashModerationState::TRASHED, '!=')
      ->sort('content_entity_revision_id', 'DESC')
      ->range(0, 1)
      ->execute();
    $revision_ids = array_keys($entities);
    $revision_id = reset($revision_ids);
    $content_moderation_state = $storage->loadRevision($revision_id);
    $entity->set('moderation_state', $content_moderation_state->moderation_state->target_id);
    $entity->save();
  }

}
