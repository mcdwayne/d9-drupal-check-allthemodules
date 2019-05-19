<?php

namespace Drupal\workflow_moderation;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Tracks metadata about revisions across entities.
 */
class RevisionTracker {

  /**
   * {@inheritdoc}
   */
  public function setLatestRevision($entity_type_id, $entity_id, $langcode, $revision_id) {
    try {
      $this->recordLatestRevision($entity_type_id, $entity_id, $langcode, $revision_id);
    }
    catch (DatabaseExceptionWrapper $e) {
      $this->ensureTableExists();
      $this->recordLatestRevision($entity_type_id, $entity_id, $langcode, $revision_id);
    }
    return $this;

  }

  /**
   * Records the latest revision of a given entity.
   *
   * @param string $entity_type_id
   *   The machine name of the type of entity.
   * @param string $entity_id
   *   The Entity ID in question.
   * @param string $langcode
   *   The langcode of the revision we're saving. Each language has its own
   *   effective tree of entity revisions, so in different languages
   *   different revisions will be "latest".
   * @param int $revision_id
   *   The revision ID that is now the latest revision.
   *
   * @return int
   *   One of the valid returns from a merge query's execute method.
   */
  protected function recordLatestRevision($entity_type_id, $entity_id, $langcode, $revision_id) {
    return db_merge('workflow_revision_tracker')
      ->keys([
        'entity_type'   => $entity_type_id,
        'entity_id'     => $entity_id,
        'langcode'      => $langcode,
      ])
      ->fields([
        'revision_id'   => $revision_id,
      ])
      ->execute();

  }

  /**
   * Checks if the table exists.
   *
   * @return bool
   *   TRUE if the table was created, FALSE otherwise.
   */
  protected function ensureTableExists() {
    if (!db_table_exists('workflow_revision_tracker')) {
        return TRUE;
    }
    return FALSE;

  }

}
