<?php

namespace Drupal\acsf\Event;

use Drupal\comment\CommentStorage;

/**
 * Comment storage class (using a SQL backend) which ignores load failures.
 *
 * The idea of this class is to prefer scrubbing over consistency; we don't care
 * much about load failures since the only reason for loading comments is being
 * able to delete them.
 */
class AcsfDuplicationScrubCommentStorage extends CommentStorage {

  /**
   * Invokes hook_entity_storage_load() while catching exceptions thrown.
   *
   * Unlike SqlContentEntityStorage's implementation, this prevents a
   * hook_comment_storage_load() implementation somewhere in contrib from
   * throwing exceptions while loading orphaned comments, and causing
   * Wip failures.
   *
   * Issue https://www.drupal.org/node/2614720 was filed and this method was
   * written assuming that Drupal Core itself was throwing exceptions which
   * should be caught, while loading orphaned comments. Unfortunately that's not
   * the case: RDF module throws a fatal error (not an exception). So now this
   * method does not solve a known problem; it's just a semi random extra
   * precaution in case a contrib module does funny things. This may be deleted
   * if we value minimizing code over supporting random theoretical failures.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   List of entities, keyed on the entity ID.
   */
  protected function invokeStorageLoadHook(array &$entities) {
    if (!empty($entities)) {
      // Call hook_entity_storage_load().
      foreach ($this->moduleHandler()->getImplementations('entity_storage_load') as $module) {
        $function = $module . '_entity_storage_load';
        try {
          $function($entities, $this->entityTypeId);
        }
        catch (\Exception $e) {
          // Don't care.
        }
      }
      // Call hook_TYPE_storage_load().
      foreach ($this->moduleHandler()->getImplementations($this->entityTypeId . '_storage_load') as $module) {
        $function = $module . '_' . $this->entityTypeId . '_storage_load';
        try {
          $function($entities);
        }
        catch (\Exception $e) {
          // Don't care.
        }
      }
    }
  }

  /**
   * Deletes orphaned comments without having to load the full entities first.
   *
   * The regular EntityStorageInterface::delete() expects fully loaded entities
   * as arguments but -because of the above- we cannot load orphaned comments.
   * So we'll query for IDs, and mimic delete-related methods so they need
   * IDs as an argument instead of full entities.
   *
   * @param int $limit
   *   (optional) Maximum number of comments to delete in one go.
   * @param int $already_processed_min_id
   *   (optional) If specified and >0, only delete items with an ID lower than
   *   this. 0 is interpreted as "no deletion is necessary".
   *
   * @return array
   *   The orphaned items that were found, and possibly deleted.
   */
  public function deleteOrphanedItems($limit = 0, $already_processed_min_id = -1) {
    $cids = $this->getOrphanedItems($limit, $already_processed_min_id);
    if ($cids) {

      // First, check if these comments have children which are not orphaned
      // (i.e. the commented node and user do exist; the parent comment is only
      // orphaned because its parent does not exist). If so, add these to the
      // list. (These cannot be loaded without generating fatal errors either,
      // because rdf_comment_storage_load() calls $comment->getParentComment()
      // which tries to load the whole parent comment which recursively etc.
      // until rdf_comment_storage_load() processes the orphaned parent and
      // crashes.)
      $uid_and_entity_ok = array_filter($cids);
      $child_cids = [];
      if ($uid_and_entity_ok) {
        // Database statement copied/changed from $this->getChildCids():
        $child_cids = $this->database->select('comment_field_data', 'c')
          ->fields('c', ['cid'])
          ->condition('pid', array_keys($uid_and_entity_ok), 'IN')
          ->condition('default_langcode', 1)
          ->execute()
          ->fetchCol();
      }

      $cids = array_merge(array_keys($cids), $child_cids);

      // Mimic the parts of CommentStorage::delete() that are possible.
      // The call structure:
      // - Comment::preDelete: is empty.
      // - invokeHook('predelete'): needs entity.
      // - doDelete():
      //   - invokeFieldMethod('delete'): needs entity.
      //   - doDeleteFieldItems(): can be mimicked.
      $this->doDeleteFieldItemsById($cids);
      // - resetCache()
      $this->resetCache($cids);
      // - Comment::postDelete:
      //   - deletes child comments: done above.
      //   - deletes statistics: copying CommentStatistics::delete() code here:
      $this->database->delete('comment_entity_statistics')
        ->condition('entity_id', $cids, 'IN')
        ->condition('entity_type', 'comment')
        ->execute();
      // - invokeHook('postdelete'): needs entity.
    }

    return $cids;
  }

  /**
   * Gets a list of orphaned comment IDs.
   *
   * 'orphaned' means having an invalid user, commented entity, or parent
   * comment. "Commented entity" is only checked for nodes (not other entity
   * types).
   *
   * @param int $limit
   *   (optional) Maximum number of comment IDs to fetch in one go.
   * @param int $already_processed_min_id
   *   (optional) If specified and >0, only fetch IDs lower than this. 0 is
   *   interpreted as "no action is necessary".
   *
   * @return array
   *   An indexed array indexed by the relevant comment IDs, with a value of 1
   *   if the user and commented entity are valid (so only the parent comment
   *   is wrong), and 0 otherwise.
   */
  protected function getOrphanedItems($limit = 0, $already_processed_min_id = -1) {

    if ($already_processed_min_id == 0) {
      return [];
    }

    $where = "u.uid IS NULL OR (n.nid IS NULL and c.entity_type = 'node')
      OR (pc.cid IS NULL AND c.pid > 0)";
    $args = [];
    if ($already_processed_min_id > 0) {
      $where = "($where) AND c.cid < :processed";
      $args[':processed'] = $already_processed_min_id;
    }
    $query = "SELECT c.cid, CASE WHEN u.uid IS NULL OR (n.nid IS NULL and c.entity_type = 'node') THEN 0 ELSE 1 END AS validref
      FROM {comment_field_data} c
      LEFT JOIN {users} u ON c.uid = u.uid
      LEFT JOIN {node} n ON c.entity_id = n.nid
      LEFT JOIN {comment} pc ON c.pid = pc.cid
      WHERE $where ORDER BY c.cid DESC";

    $statement = $limit ? $this->database->queryRange($query, 0, $limit, $args)
      : $this->database->query($query, $args);
    return $statement->fetchAllKeyed();
  }

  /**
   * Deletes entity field values from the storage.
   *
   * This is a near copy of SqlContentEntityStorage::doDeleteFieldItems() except
   * it takes ids as argument instead of entities.
   *
   * @param array $ids
   *   The entity ids.
   */
  protected function doDeleteFieldItemsById(array $ids) {
    $this->database->delete($this->entityType->getBaseTable())
      ->condition($this->idKey, $ids, 'IN')
      ->execute();

    if ($this->revisionTable) {
      $this->database->delete($this->revisionTable)
        ->condition($this->idKey, $ids, 'IN')
        ->execute();
    }

    if ($this->dataTable) {
      $this->database->delete($this->dataTable)
        ->condition($this->idKey, $ids, 'IN')
        ->execute();
    }

    if ($this->revisionDataTable) {
      $this->database->delete($this->revisionDataTable)
        ->condition($this->idKey, $ids, 'IN')
        ->execute();
    }

    // Delete as many dedicated field tables as we can find. This is slightly
    // different from the original: since we don't know the original entities'
    // bundles, we loop through all bundles that exist for a comment.
    foreach (array_keys($this->entityManager->getBundleInfo('comment')) as $bundle) {
      $this->deleteFromDedicatedTablesById($ids, $bundle);
    }
  }

  /**
   * Deletes values of fields in dedicated tables for all revisions.
   *
   * This is a lookalike of SqlContentEntityStorage::deleteFromDedicatedTables()
   * which takes an array of ids + a bundle as arguments, instead of a single
   * entity.
   *
   * @param array $ids
   *   The entity ids.
   * @param string $bundle
   *   A bundle id; must be an existing bundle for 'comment'.
   */
  protected function deleteFromDedicatedTablesById(array $ids, $bundle) {
    $table_mapping = $this->getTableMapping();
    foreach ($this->entityManager->getFieldDefinitions('comment', $bundle) as $field_definition) {
      $storage_definition = $field_definition->getFieldStorageDefinition();
      if (!$table_mapping->requiresDedicatedTableStorage($storage_definition)) {
        continue;
      }
      $table_name = $table_mapping->getDedicatedDataTableName($storage_definition);
      $revision_name = $table_mapping->getDedicatedRevisionTableName($storage_definition);
      $this->database->delete($table_name)
        ->condition('entity_id', $ids, 'IN')
        ->execute();
      if ($this->entityType->isRevisionable()) {
        $this->database->delete($revision_name)
          ->condition('entity_id', $ids, 'IN')
          ->execute();
      }
    }
  }

}
